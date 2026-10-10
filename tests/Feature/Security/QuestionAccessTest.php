<?php

namespace Tests\Feature\Security;

use App\Models\Question;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Individuelle Fragen: Lagerleitung nur im eigenen Lager, nur Formularfelder.
 */
class QuestionAccessTest extends TestCase
{
    public function test_manager_can_create_and_update_question_of_own_camp(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager);

        $this->from(route('dashboard.questions.index'))
            ->post(route('dashboard.questions.store'), ['name' => 'Neue Frage', 'sortindex' => 2, 'camp_id' => $scenario->otherCamp->id, 'active' => 0])
            ->assertRedirect(route('dashboard.questions.index'));
        $question = Question::where('name', 'Neue Frage')->firstOrFail();
        $this->assertSame($scenario->camp->id, $question->camp_id);
        $this->assertTrue($question->active);

        $this->patch(route('dashboard.questions.update', $question), ['name' => 'Geändert', 'sortindex' => 3, 'active' => 1, 'camp_id' => $scenario->otherCamp->id])
            ->assertRedirect('/dashboard/questions');
        $question = $question->fresh();
        $this->assertSame('Geändert', $question->name);
        $this->assertSame($scenario->camp->id, $question->camp_id);
    }

    public function test_manager_cannot_edit_update_or_delete_question_of_other_camp(): void
    {
        $scenario = CampScenario::create();
        $foreign = Question::factory()->create(['camp_id' => $scenario->otherCamp->id, 'name' => 'Fremde Frage']);
        $this->actingAs($scenario->manager);

        $this->get(route('dashboard.questions.edit', $foreign))->assertForbidden();
        $this->patch(route('dashboard.questions.update', $foreign), ['name' => 'übernommen', 'active' => 1])->assertForbidden();
        $this->delete(route('dashboard.questions.destroy', $foreign))->assertForbidden();

        $this->assertSame('Fremde Frage', $foreign->fresh()->name);
    }

    public function test_manager_can_delete_question_of_own_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)->delete(route('dashboard.questions.destroy', $scenario->question));

        $this->assertNull($scenario->question->fresh());
    }
}
