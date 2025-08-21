<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Media;
use App\Models\Tag;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $tags = Tag::all();

        // Create 30 videos. The factory's `configure` method will handle creating the encode.
        Media::factory(30)->video()->create()->each(function ($media) use ($tags) {
            $media->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
        });

        // Create 20 images. The factory will also create the corresponding encode.
        Media::factory(20)->image()->create()->each(function ($media) use ($tags) {
            $media->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
        });
    }
}
