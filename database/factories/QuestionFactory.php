<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'camp_id' => Camp::factory(),
            'active' => true,
            'sortindex' => 0,
        ];
    }
}
