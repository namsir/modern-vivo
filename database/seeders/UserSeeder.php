<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a specific user for testing
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password'
        ]);

        // Create 10 additional random users
        User::factory(10)->create();
    }
}
