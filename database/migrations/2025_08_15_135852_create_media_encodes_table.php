<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_encodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('preset_id')->nullable(); // For AWS, etc.
            $table->string('status')->default('pending'); // e.g., pending, processing, complete, failed
            $table->text('status_details')->nullable(); // For AWS feedback, etc.
            $table->string('type')->default('mp4'); // e.g., hls, mp4
            $table->string('resolution')->nullable(); // e.g., 720p
            $table->string('url');
            $table->timestamps(); // Corresponds to 'created' and 'modified'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_encodes');
    }
};
