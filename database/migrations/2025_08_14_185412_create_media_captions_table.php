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
        Schema::create('media_captions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->foreignId('caption_profile_id')->nullable()->constrained()->onDelete('set null');

            $table->string('status')->default('requested'); // e.g., requested, processing, pending_approval, approved, rejected

            $table->string('requested_by')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->string('approved_by')->nullable();

            $table->string('language');
            $table->string('language_code');
            $table->string('order_id')->nullable(); // From the third-party service
            $table->text('reason')->nullable(); // For rejection/approval notes
            $table->longText('caption')->nullable(); // The VTT content

            $table->timestamps(); // Corresponds to 'created' and 'modified'
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_captions');
    }
};
