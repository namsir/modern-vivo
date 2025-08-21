<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caption_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "Rev.com", "3Play Media"
            $table->string('api_key')->nullable();
            $table->json('configurations')->nullable(); // For storing extra settings
            $table->boolean('is_active')->default(false);
            $table->string('vendor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caption_profiles');
    }
};
