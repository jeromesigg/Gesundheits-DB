<?php

namespace Tests\Feature\HealthForm;

use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Lagerleitung erstellt ein Gesundheitsblatt von Hand.
 */
class HealthFormCreateTest extends TestCase
{
    public function test_manager_creates_health_form_with_code_and_questions(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->post(route('healthforms.store'), $this->payload())
            ->assertOk()
            ->assertViewIs('healthform.edit');

        $healthForm = HealthForm::where('nickname', 'Igel')->firstOrFail();
        $this->assertSame($scenario->camp->id, $healthForm->camp_id);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $healthForm->code);
        $healthInformation = HealthInformation::where('code', $healthForm->code)->firstOrFail();
        $this->assertSame($scenario->camp->id, $healthInformation->camp_id);
        $this->assertTrue(HealthInformationQuestion::where('health_information_id', $healthInformation->id)->where('question_id', $scenario->question->id)->exists());
    }

    public function test_with_independent_form_fill_manager_returns_to_list(): void
    {
        $scenario = CampScenario::create();
        $scenario->camp->update(['independent_form_fill' => true]);

        $this->actingAs($scenario->manager)
            ->post(route('healthforms.store'), $this->payload())
            ->assertOk()
            ->assertViewIs('dashboard.healthform.index');
    }

    public function test_required_names_are_validated(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->post(route('healthforms.store'), ['nickname' => '', 'first_name' => '', 'last_name' => ''])
            ->assertSessionHasErrors(['nickname', 'first_name', 'last_name']);
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return ['nickname' => 'Igel', 'first_name' => 'Ida', 'last_name' => 'Igel', 'group_text' => 'Cevi Zürich', 'birthday' => '2013-04-01'];
    }
}
