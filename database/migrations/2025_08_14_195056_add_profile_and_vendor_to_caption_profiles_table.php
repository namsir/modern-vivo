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
        Schema::table('caption_profiles', function (Blueprint $table) {
            $table->string('profile')->nullable()->after('api_key');
        });
    }

    public function down(): void
    {
        Schema::table('caption_profiles', function (Blueprint $table) {
            $table->dropColumn(['profile']);
        });
    }
};
