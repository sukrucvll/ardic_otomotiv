<?php

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(array_keys(config('training.categories'))),
            'description' => fake()->paragraph(),
            'duration_hours' => fake()->numberBetween(1, 16),
            'is_mandatory' => fake()->boolean(),
        ];
    }
}
