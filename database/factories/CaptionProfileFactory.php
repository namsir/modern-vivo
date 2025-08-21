<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CaptionProfile>
 */
class CaptionProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Captions',
            'api_key' => Str::random(32),
            'profile' => 'Default',
            'vendor' => fake()->company(),
            'is_active' => false,
        ];
    }
}
