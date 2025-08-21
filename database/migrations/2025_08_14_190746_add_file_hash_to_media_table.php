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
        Schema::table('media', function (Blueprint $table) {
            // SHA-256 hashes are 64 characters long.
            // The column is nullable because existing records won't have a hash.
            // We add an index to make searching for duplicates extremely fast.
            $table->string('file_hash', 64)->nullable()->unique()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropUnique(['file_hash']);
            $table->dropColumn('file_hash');
        });
    }
};
