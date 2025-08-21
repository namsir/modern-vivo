<?php

namespace App\Http\Controllers;

use App\Mail\MediaEncodeFailed;
use App\Models\Media;
use App\Models\MediaCaption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook notifications from AWS MediaConvert.
     */
    public function handleMediaConvert(Request $request)
    {
        Log::info('AWS MediaConvert Webhook Received:', $request->all());

        $payload = $request->input('detail', []);
        $status = $payload['status'] ?? null;
        $mediaId = $payload['userMetadata']['media_id'] ?? null;

        if (!$mediaId) {
            Log::warning('MediaConvert webhook received without a media_id in userMetadata.');
            return response()->json(['status' => 'error', 'message' => 'No media_id provided.'], 400);
        }

        $media = Media::find($mediaId);
        if (!$media) {
            Log::error("MediaConvert webhook: Media with ID {$mediaId} not found.");
            return response()->json(['status' => 'error', 'message' => 'Media not found.'], 404);
        }

        // LOG EVENT: Webhook received from AWS
        $media->events()->create([
            'event_type' => 'Received AWS MediaConvert Webhook',
            'status' => 'info',
            'details' => "Job ID: {$payload['jobId']}\nStatus: {$status}",
        ]);

        if ($status === 'COMPLETE') {
            // --- Move the original source file ---
            $originalEncode = $media->encodes()->orderBy('id', 'asc')->first();
            if ($originalEncode) {
                $sourceBucket = env('AWS_BUCKET');
                $destinationBucket = env('AWS_PROCESSED_BUCKET');
                $sourceKey = $originalEncode->url; // The original filename/key

                // Copy the file from the raw bucket to the processed bucket
                Storage::disk('s3')->copy($sourceKey, $sourceKey); // This assumes both disks point to different buckets but can share keys

                // A more explicit way if you have two separate disk configurations:
                // Storage::disk('s3_processed')->put($sourceKey, Storage::disk('s3_raw')->get($sourceKey));

                // Delete the original file from the raw bucket
                Storage::disk('s3')->delete($sourceKey);

                // Update the original encode's URL to its new public location
                $region = env('AWS_DEFAULT_REGION');
                $publicUrl = "https://{$destinationBucket}.s3.{$region}.amazonaws.com/{$sourceKey}";
                $originalEncode->update(['url' => $publicUrl]);
            }
            // --- End moving source file ---

            $outputDetails = $payload['outputGroupDetails'][0]['outputDetails'] ?? [];

            foreach ($outputDetails as $output) {
                $videoDetails = $output['videoDetails'];
                $s3Url = $output['outputFilePaths'][0];

                $relativePath = ltrim(parse_url($s3Url, PHP_URL_PATH), '/');

                $bucket = env('AWS_PROCESSED_BUCKET');
                $region = env('AWS_DEFAULT_REGION');
                $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$relativePath}";

                $media->encodes()->create([
                    'width' => $videoDetails['widthInPx'],
                    'height' => $videoDetails['heightInPx'],
                    'status' => 'complete',
                    'type' => 'mp4',
                    'resolution' => "{$videoDetails['heightInPx']}p",
                    'url' => $publicUrl,
                ]);
            }

            $media->update(['status' => 'Published']);
            Log::info("Media ID {$mediaId} successfully transcoded and published.");
// LOG EVENT: AWS job complete
            $media->events()->create([
                'event_type' => 'AWS Transcode Complete',
                'status' => 'success',
            ]);
        } elseif ($status === 'ERROR') {
            $errorMessage = $payload['errorMessage'] ?? 'Unknown error from AWS MediaConvert.';

            $media->update([
                'status' => 'Failed',
                'status_details' => $errorMessage,
            ]);
// LOG EVENT: AWS job failed
            $media->events()->create([
                'event_type' => 'AWS Transcode Failed',
                'status' => 'error',
                'details' => $errorMessage,
            ]);
            Log::error("MediaConvert job failed for Media ID {$mediaId}. Reason: " . $errorMessage);

            $adminEmail = env('ADMIN_EMAIL');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new MediaEncodeFailed($media, $errorMessage));
            }
        }

        return response()->json(['status' => 'success']);
    }


    /**
     * Handle incoming webhook notifications from 3Play Media.
     */
    public function handle3Play(Request $request)
    {
        Log::info('3Play Media Webhook Received:', $request->all());

        $orderId = $request->input('transcript_id'); // Assuming the key is 'transcript_id'
        $status = $request->input('status');

        if (!$orderId || $status !== 'complete') {
            return response()->json(['status' => 'ignored']);
        }

        $captionRequest = MediaCaption::where('order_id', $orderId)->first();

        if (!$captionRequest) {
            Log::warning("3Play webhook: MediaCaption with Order ID {$orderId} not found.");
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        // Log the event even if it's not complete yet
        $captionRequest->media->events()->create([
            'event_type' => 'Received 3Play Webhook',
            'status' => 'info',
            'details' => "Order ID: {$orderId}\nStatus: {$status}",
        ]);

        try {
            // Fetch the VTT file from 3Play's provided URL
            // This URL structure is an example and may need to be adjusted
            $apiKey = $captionRequest->captionProfile->api_key;
            $vttUrl = "[https://api.3playmedia.com/v3/transcripts/](https://api.3playmedia.com/v3/transcripts/){$orderId}/text?apikey={$apiKey}&output_format=vtt";

            $response = Http::get($vttUrl);

            if ($response->successful()) {
                $captionRequest->update([
                    'caption' => $response->body(),
                    'status' => 'completed_by_vendor', // A new status for admin review
                ]);
                Log::info("Successfully updated caption for Order ID: {$orderId}");

                // LOG EVENT: Caption successfully downloaded
                $captionRequest->media->events()->create([
                    'event_type' => 'Caption Downloaded from Vendor',
                    'status' => 'success',
                    'details' => "Order ID: {$orderId}",
                ]);
            } else {
                throw new \Exception("Failed to download VTT file from 3Play for Order ID: {$orderId}");
            }
        } catch (\Exception $e) {
            $captionRequest->update(['status' => 'failed_vendor_retrieval']);
            // LOG EVENT: Failed to download caption
            $captionRequest->media->events()->create([
                'event_type' => 'Failed to Download Caption from Vendor',
                'status' => 'error',
                'details' => $e->getMessage(),
            ]);
            report($e);
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'success']);
    }
}
