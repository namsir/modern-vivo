<?php

namespace App\Jobs;

use App\Models\Media;
use Aws\MediaConvert\MediaConvertClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranscodeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    private Media $media;

    public function __construct(
        public int $mediaId
    )
    {
        $this->media = Media::findOrFail($this->mediaId);
    }

    public function handle(): void
    {
// LOG EVENT: Job started
        $this->media->events()->create([
            'event_type' => 'Transcode Job Started',
            'status' => 'info',
        ]);
        $originalEncode = $this->media->encodes()->orderBy('id', 'asc')->first();

        if (!$originalEncode) {
            Log::warning("No original encode found for Media ID {$this->media->id}. Skipping transcode.");
            return;
        }

        $mediaConvertClient = new MediaConvertClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'endpoint' => env('AWS_MEDIACONVERT_ENDPOINT'),
        ]);

        $targetResolutions = [
            '1080' => ['width' => 1920, 'height' => 1080, 'bitrate' => 5000000],
            '720' => ['width' => 1280, 'height' => 720, 'bitrate' => 2800000],
            '480' => ['width' => 854, 'height' => 480, 'bitrate' => 1400000],
            '360' => ['width' => 640, 'height' => 360, 'bitrate' => 800000],
        ];

        $outputs = [];
        foreach ($targetResolutions as $quality => $dims) {
            if ($dims['height'] < $originalEncode->height) {
                // THE FIX: Fill in the complete settings for each output.
                $outputs[] = [
                    'NameModifier' => "_{$quality}p",
                    'VideoDescription' => [
                        'Width' => $dims['width'],
                        'Height' => $dims['height'],
                        'CodecSettings' => [
                            'Codec' => 'H_264',
                            'H264Settings' => [
                                'MaxBitrate' => $dims['bitrate'],
                                'RateControlMode' => 'QVBR',
                                'SceneChangeDetect' => 'TRANSITION_DETECTION',
                            ],
                        ],
                    ],
                    'AudioDescriptions' => [
                        [
                            'AudioSourceName' => 'Audio Selector 1',
                            'CodecSettings' => [
                                'Codec' => 'AAC',
                                'AacSettings' => [
                                    'Bitrate' => 128000,
                                    'CodingMode' => 'CODING_MODE_2_0',
                                    'SampleRate' => 48000,
                                ],
                            ],
                        ],
                    ],
                    'ContainerSettings' => [
                        'Container' => 'MP4',
                        'Mp4Settings' => [
                            'MoovPlacement' => 'PROGRESSIVE_DOWNLOAD',
                        ],
                    ],
                ];
            }
        }

        if (empty($outputs)) {
            $this->media->update(['status' => 'Published']);
            Log::info("No smaller resolutions to transcode for Media ID {$this->media->id}. Marking as published.");
            return;
        }

        $destinationFolder = $this->media->media_type . 's/' . $this->media->id;
        $destination = 's3://' . env('AWS_PROCESSED_BUCKET') . '/' . $destinationFolder . '/';

        Log::info("Setting MediaConvert destination to: {$destination}");

        $jobSettings = [
            'Inputs' => [
                [
                    'AudioSelectors' => ['Audio Selector 1' => ['DefaultSelection' => 'DEFAULT']],
                    'VideoSelector' => ['ColorSpace' => 'FOLLOW'],
                    'FilterEnable' => 'AUTO',
                    'FileInput' => 's3://' . env('AWS_BUCKET') . '/' . $originalEncode->url,
                ],
            ],
            'OutputGroups' => [
                [
                    'Name' => 'File Group',
                    'Outputs' => $outputs,
                    'OutputGroupSettings' => [
                        'Type' => 'FILE_GROUP_SETTINGS',
                        'FileGroupSettings' => [
                            'Destination' => $destination,
                        ],
                    ],
                ],
            ],
            'DestinationSettings' => [
                'S3Settings' => [
                    'AccessControl' => [
                        'CannedAcl' => 'PUBLIC_READ'
                    ]
                ]
            ],
        ];

        try {
            $mediaConvertClient->createJob([
                'Role' => env('AWS_MEDIACONVERT_ROLE_ARN'),
                'Settings' => $jobSettings,
                'UserMetadata' => [
                    'media_id' => $this->media->id,
                ],
            ]);
            // LOG EVENT: AWS job created
            $this->media->events()->create([
                'event_type' => 'Sent to AWS MediaConvert',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            // LOG EVENT: AWS job creation failed
            $this->media->events()->create([
                'event_type' => 'Failed to Create AWS Job',
                'status' => 'error',
                'details' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        if (isset($this->media)) {
            $this->media->update(['status' => 'Failed']);
        }
        report($exception);
    }
}
