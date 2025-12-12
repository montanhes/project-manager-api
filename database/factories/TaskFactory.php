<?php

namespace Database\Factories;

use App\Enums\TaskDifficulty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'completed' => fake()->boolean(),
            'difficulty' => fake()->randomElement(TaskDifficulty::cases()),
        ];
    }
}
