<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_tag', function (Blueprint $table) {
            // Rename the old foreign key to the new one
            $table->renameColumn('video_id', 'media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_tag', function (Blueprint $table) {
            // Defines how to undo the change if you roll back
            $table->renameColumn('media_id', 'video_id');
        });
    }
};
