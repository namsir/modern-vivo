<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(2),
            'status' => 'Published',
            'file_hash' => fake()->sha256(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    public function video(): Factory
    {
        return $this->state(fn (array $attributes) => ['media_type' => 'video']);
    }

    public function image(): Factory
    {
        return $this->state(fn (array $attributes) => ['media_type' => 'image']);
    }

    public function pdf(): Factory
    {
        return $this->state(fn (array $attributes) => ['media_type' => 'pdf']);
    }

    public function doc(): Factory
    {
        return $this->state(fn (array $attributes) => ['media_type' => 'doc']);
    }

    /**
     * After creating a media item, create a corresponding encode for it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Media $media) {
            $path = 'placeholders/document-placeholder.docx';
            $type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $width = 0;
            $height = 0;
            $resolution = 'doc';

            if ($media->media_type === 'video') {
                $path = 'placeholders/video-placeholder.mp4';
                $type = 'video/mp4';
                $width = 1280;
                $height = 720;
                $resolution = "{$height}p";
            } elseif ($media->media_type === 'image') {
                $path = 'placeholders/image-placeholder.jpg';
                $type = 'image/jpeg';
                $width = 1920;
                $height = 1080;
                $resolution = "{$height}p";
            } elseif ($media->media_type === 'pdf') {
                $path = 'placeholders/pdf-placeholder.pdf';
                $type = 'application/pdf';
            }

            $media->encodes()->create([
                'url' => $path,
                'type' => $type,
                'width' => $width,
                'height' => $height,
                'resolution' => $resolution,
                'status' => 'complete',
            ]);
        });
    }
}
