<?php

namespace App\Http\Controllers;

use App\Jobs\TranscodeMediaJob;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\Models\CaptionProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $media = Media::query()
            ->where('user_id', Auth::id())
            ->with(['user', 'tags', 'encodes'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('media/Index', [
            'media' => $media,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('media/Upload', [
            'allTags' => Tag::all(['id', 'name']),
        ]);
    }

    /**
     * Handles the uploading of a single file chunk.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'upload_id' => 'required|string',
            'original_filename' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
            'caption_requested' => 'required|in:true,false',
        ]);

        $file = $request->file('chunk');
        $chunkIndex = $request->input('chunk_index');
        $totalChunks = $request->input('total_chunks');
        $uploadId = $request->input('upload_id');

        $tempPath = "chunks/{$uploadId}";
        $file->storeAs($tempPath, $chunkIndex, 'local');

        $uploadedChunksCount = count(Storage::disk('local')->files($tempPath));

        if ($uploadedChunksCount === (int)$totalChunks) {
            try {
                $media = $this->assembleFile(
                    $tempPath,
                    $request->input('original_filename'),
                    [
                        'title' => $request->input('title'),
                        'description' => $request->input('description'),
                        'tags' => json_decode($request->input('tags', '[]')),
                        'caption_requested' => $request->input('caption_requested') === 'true',
                    ]
                );
            } catch (\Exception $e) {
                Storage::disk('local')->deleteDirectory($tempPath);
                return response()->json(['message' => $e->getMessage()], 409);
            }

            Storage::disk('local')->deleteDirectory($tempPath);

            return response()->json(['message' => 'File assembled successfully', 'media' => $media], 201);
        }

        return response()->json(['message' => 'Chunk uploaded successfully'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Media $media)
    {
        return Inertia::render('media/Edit', [
            'media' => $media->load(['captions', 'tags', 'encodes']),
            'allTags' => Tag::all(),
            'users' => User::all(['id', 'name']),
        ]);
    }

    /**
     * Assembles the final file, creates the Media record, and handles caption requests.
     */
    private function assembleFile(string $tempPath, string $originalFilename, array $metadata): Media
    {
        $mimeType = mime_content_type(Storage::disk('local')->path("{$tempPath}/0"));
        $mediaType = $this->getMediaType($mimeType);

        $fileHandle = tmpfile();
        $totalChunks = count(Storage::disk('local')->files($tempPath));
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path("{$tempPath}/{$i}");
            fwrite($fileHandle, file_get_contents($chunkPath));
        }

        rewind($fileHandle);
        $fileHash = hash_file('sha256', stream_get_meta_data($fileHandle)['uri']);

        $existingMedia = Media::where('file_hash', $fileHash)->first();
        if ($existingMedia) {
            fclose($fileHandle);
            throw new \Exception("This file already exists (Media ID: {$existingMedia->id}). Upload cancelled.");
        }

        // --- THE FIX: Create the media record FIRST to get an ID ---
        $media = Media::create([
            'user_id' => auth()->id(),
            'title' => $metadata['title'],
            'description' => $metadata['description'],
            'status' => 'Processing', // Start with processing status
            'media_type' => $mediaType,
            'file_hash' => $fileHash,
        ]);



        // --- Now, build the nested path using the new media ID ---
        $finalDirectory = "{$mediaType}s/{$media->id}"; // e.g., videos/17
        $finalFileName = Str::random(40) . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
        $finalFilePath = "{$finalDirectory}/{$finalFileName}";

        rewind($fileHandle);
        Storage::disk('s3')->put($finalFilePath, $fileHandle, ['ACL' => 'bucket-owner-full-control']);

        $quality = 0;
        $width = 0;

        if ($mediaType === 'video') {
            $tempFileForProbing = stream_get_meta_data($fileHandle)['uri'];
            try {
                $command = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 \"{$tempFileForProbing}\"";
                $result = Process::run($command);
                if ($result->successful()) {
                    $dimensions = explode('x', trim($result->output()));
                    if (count($dimensions) === 2) {
                        [$width, $quality] = $dimensions;
                    }
                }
            } catch (\Exception $e) { report($e); }
        }

        fclose($fileHandle);

        // LOG EVENT: File upload and assembly complete
        $media->events()->create([
            'event_type' => 'File Assembled & Uploaded to S3',
            'status' => 'success',
            'details' => "Original file stored at s3://" . env('AWS_BUCKET') . "/{$finalFilePath}",
        ]);

        // Create the first, original encode record with the new nested path
        $media->encodes()->create([
            'width' => (int)$width,
            'height' => (int)$quality,
            'status' => 'complete',
            'type' => $mimeType,
            'resolution' => "{$quality}p",
            'url' => $finalFilePath, // Save the nested path
        ]);

        if ($media->media_type === 'video') {
            TranscodeMediaJob::dispatch($media->id);
            // LOG EVENT: Transcoding job dispatched
            $media->events()->create([
                'event_type' => 'Transcode Job Dispatched',
                'status' => 'info',
            ]);
        } else {
            // If it's not a video, it's done processing.
            $media->update(['status' => 'Published']);
        }

        if ($media->media_type === 'video' && $metadata['caption_requested']) {
            $activeProfile = CaptionProfile::where('is_active', true)->first();
            $media->captions()->create([
                'status' => 'requested',
                'requested_by' => auth()->user()->name,
                'language' => 'English',
                'language_code' => 'en',
                'caption_profile_id' => $activeProfile?->id,
            ]);
        }

        if (!empty($metadata['tags'])) {
            $tagIds = [];
            foreach ($metadata['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            $media->tags()->sync($tagIds);
        }

        $media->load('tags', 'encodes');

        return $media;
    }

    /**
     * Helper function to determine media type from MIME type.
     */
    private function getMediaType(string $mimeType): string
    {
        // This helper function already handles various video mime types correctly
        if (Str::startsWith($mimeType, 'video/')) return 'video';
        if (Str::startsWith($mimeType, 'image/')) return 'image';
        if ($mimeType === 'application/pdf') return 'pdf';
        return 'doc';
    }



    /**
     * Generate a VTT file for a media item's captions.
     */
    public function captionsVtt(Media $media)
    {
        $captions = $media->captions()->where('status', 'approved')->orderBy('id')->get();

        $vttContent = "WEBVTT\n\n";

        // Since the VTT content is stored directly, we just need the first approved caption.
        if ($captions->isNotEmpty()) {
            $vttContent = $captions->first()->caption;
        }

        // Return the content with the correct VTT MIME type
        return new Response($vttContent, 200, [
            'Content-Type' => 'text/vtt',
            'Content-Disposition' => 'inline; filename="captions.vtt"',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Media $media)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        DB::transaction(function () use ($media, $validated) {
            // Update the main media details
            $media->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            // Sync Tags
            if (isset($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    // Find the tag or create it if it doesn't exist
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    $tagIds[] = $tag->id;
                }
                // sync() handles attaching, detaching, and keeping existing tags
                $media->tags()->sync($tagIds);
            }
        });

        return back()->with('success', 'Media details updated successfully.');
    }
}
