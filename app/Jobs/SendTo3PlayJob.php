<?php

namespace App\Jobs;

use App\Models\MediaCaption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTo3PlayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public MediaCaption $captionRequest
    ) {
        //
    }

    public function handle(): void
    {
        $this->captionRequest->load(['media.encodes', 'captionProfile']);

        $profile = $this->captionRequest->captionProfile;
        $media = $this->captionRequest->media;

        // 1. Ensure we have an active profile with an API key
        if (!$profile || !$profile->is_active || !$profile->api_key) {
            $this->fail(new \Exception("No active caption profile with an API key for MediaCaption ID: {$this->captionRequest->id}"));
            return;
        }

        // 2. Get the highest quality video encode URL
        $sourceEncode = $media->encodes()->orderBy('height', 'desc')->first();
        if (!$sourceEncode) {
            $this->fail(new \Exception("No source encode found for Media ID: {$media->id}"));
            return;
        }

        // LOG EVENT: Job started
        $media->events()->create([
            'event_type' => 'Vendor Caption Job Started',
            'status' => 'info',
            'details' => "Attempting to send request to 3Play Media for MediaCaption ID: {$this->captionRequest->id}",
        ]);

        // 3. Make the API request to the third-party service (example for 3Play Media)
        try {
            $response = Http::post('https://api.3playmedia.com/v3/files', [
                'apikey' => $profile->api_key,
                'link' => $sourceEncode->url,
                'language_id' => 1, // 1 is typically English for 3Play
            ]);

            if ($response->successful() && $response->json('data.id')) {
                // 4. If successful, update the caption request with the order ID
                $orderId = $response->json('data.id');
                $this->captionRequest->update([
                    'status' => 'processing_by_vendor',
                    'order_id' => $orderId,
                ]);
                Log::info("Successfully sent MediaCaption ID {$this->captionRequest->id} to 3Play. Order ID: {$response->json('data.id')}");
                // LOG EVENT: Successfully sent to vendor
                $media->events()->create([
                    'event_type' => 'Successfully Sent to 3Play Media',
                    'status' => 'success',
                    'details' => "Order ID: {$orderId}",
                ]);
            } else {
                throw new \Exception("3Play API request failed: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->captionRequest->update(['status' => 'failed_vendor_submission']);
        // LOG EVENT: Failed to send to vendor
        $this->captionRequest->media->events()->create([
            'event_type' => 'Failed to Send to 3Play Media',
            'status' => 'error',
            'details' => $exception->getMessage(),
        ]);
        report($exception);
    }
}
