<?php

namespace Database\Factories;

use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthInformationQuestion>
 */
class HealthInformationQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'health_information_id' => HealthInformation::factory(),
            'question_id' => Question::factory(),
            'answer' => null,
        ];
    }
}
