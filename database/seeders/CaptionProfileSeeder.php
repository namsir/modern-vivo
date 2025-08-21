<?php

namespace Database\Seeders;

use App\Models\CaptionProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CaptionProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure no profiles are active before seeding
        CaptionProfile::query()->update(['is_active' => false]);

        // Create a few inactive sample profiles
        CaptionProfile::factory()->create([
            'name' => 'Rev.com',
            'vendor' => 'Rev',
        ]);

        CaptionProfile::factory()->create([
            'name' => '3Play Media',
            'vendor' => '3Play',
        ]);

        // Create one profile and ensure it is set to active
        CaptionProfile::factory()->create([
            'name' => 'AI Captions',
            'vendor' => 'Internal AI',
            'is_active' => true,
        ]);
    }
}
