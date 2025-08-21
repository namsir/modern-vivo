<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessVideoFilters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Media $video,
        public array $filters
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Set video status to 'Processing' to give user feedback
        $this->video->update(['status' => 'Processing']);

        // 2. Get paths for the original and new video files
        $originalRelativePath = $this->video->file_path;
        $originalAbsolutePath = Storage::disk('public')->path($originalRelativePath);

        $newFileName = 'processed-' . uniqid() . '-' . basename($originalRelativePath);
        $newRelativePath = 'videos/' . $newFileName;
        $newAbsolutePath = Storage::disk('public')->path($newRelativePath);

        // 3. Convert frontend filter values to FFmpeg's required format
        $brightness = ($this->filters['brightness'] - 100) / 100.0; // e.g., 150 -> 0.5
        $contrast = $this->filters['contrast'] / 100.0; // e.g., 120 -> 1.2
        $sepia = $this->filters['sepia'] / 100.0; // e.g., 50 -> 0.5
        $grayscale = $this->filters['grayscale'] / 100.0; // e.g., 100 -> 1.0

        // 4. Build the FFmpeg video filter (-vf) string
        $filterParts = [];
        $filterParts[] = "eq=brightness={$brightness}:contrast={$contrast}:gamma=1.0";
        if ($sepia > 0) {
            $filterParts[] = "gcolor=color=0x704214:c_weight={$sepia}";
        }
        if ($grayscale > 0) {
            // A simple desaturation. For true grayscale, more complex filters are better.
            $filterParts[] = "hue=s=0";
        }
        $filterString = implode(',', $filterParts);

        // 5. Build and run the command using Laravel's Process facade
        $command = "ffmpeg -i \"{$originalAbsolutePath}\" -vf \"{$filterString}\" \"{$newAbsolutePath}\"";

        Log::info("Running FFmpeg for Video ID {$this->video->id}: {$command}");
        $result = Process::run($command);

        // 6. Handle the result
        if ($result->successful()) {
            // On success, update the video record and delete the old file
            $this->video->update([
                'file_path' => $newRelativePath,
                'status' => 'Published',
            ]);
            Storage::disk('public')->delete($originalRelativePath);
            Log::info("Successfully processed video ID {$this->video->id}.");
        } else {
            // On failure, update status and log the error for debugging
            $this->video->update(['status' => 'Failed']);
            Log::error("FFmpeg processing failed for video ID {$this->video->id}: " . $result->errorOutput());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // If the job itself fails unexpectedly, update the status
        $this->video->update(['status' => 'Failed']);
        Log::error("Job failed for Video ID {$this->video->id}: " . $exception->getMessage());
    }
}
