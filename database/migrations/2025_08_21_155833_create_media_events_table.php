<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // e.g., 'upload_complete', 'transcode_started'
            $table->string('status'); // 'success', 'error', 'info'
            $table->text('details')->nullable(); // For storing error messages or other info
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_events');
    }
};
