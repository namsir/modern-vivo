<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captions', function (Blueprint $table) {
            // Status can be 'pending', 'approved', or 'rejected'
            $table->string('status')->default('pending')->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('captions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
