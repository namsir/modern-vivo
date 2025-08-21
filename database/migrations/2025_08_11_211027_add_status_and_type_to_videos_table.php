<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/...._add_status_and_type_to_videos_table.php
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('status')->default('Published')->after('description');
            $table->string('media_type')->default('video')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            //
        });
    }
};
