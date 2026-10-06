<?php

namespace Database\Factories;

use App\Models\Help;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Help>
 */
class HelpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->words(2, true),
            'content' => $this->faker->paragraph(),
        ];
    }
}
