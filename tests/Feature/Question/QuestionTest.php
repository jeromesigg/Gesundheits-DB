<?php

namespace Tests\Feature\Question;

use App\Models\HealthInformationQuestion;
use App\Models\Question;
use Tests\Support\CampScenario;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    public function test_new_question_is_added_to_existing_health_forms_of_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->post(route('dashboard.questions.store'), ['name' => 'Hast du Höhenangst?', 'sortindex' => 1]);

        $question = Question::where('name', 'Hast du Höhenangst?')->firstOrFail();
        foreach ([$scenario->openInfo, $scenario->finishedInfo] as $healthInfo) {
            $this->assertTrue(HealthInformationQuestion::where('question_id', $question->id)->where('health_information_id', $healthInfo->id)->whereNull('answer')->exists());
        }
        $this->assertFalse(HealthInformationQuestion::where('question_id', $question->id)->where('health_information_id', $scenario->otherInfo->id)->exists());
    }

    public function test_new_question_appears_in_existing_participant_form(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager)
            ->post(route('dashboard.questions.store'), ['name' => 'Hast du Höhenangst?', 'sortindex' => 1]);
        auth()->logout();

        $this->post('/healthform', ['camp_code' => $scenario->camp->code, 'code' => $scenario->openCode])
            ->assertOk()
            ->assertSee('Hast du Höhenangst?');
    }
}
