<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('videos', 'media');
        Schema::rename('tag_video', 'media_tag'); // Also rename the pivot table
    }

    public function down(): void
    {
        Schema::rename('media', 'videos');
        Schema::rename('media_tag', 'tag_video');
    }
};
