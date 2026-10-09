<?php

namespace Tests\Feature\HealthForm;

use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Ablauf der Teilnehmenden: Codes eingeben, Formular ausfüllen, bei Fehlern zurück zum eigenen Formular.
 */
class ParticipantFlowTest extends TestCase
{
    private CampScenario $scenario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
    }

    public function test_failed_close_returns_to_own_form_with_errors_and_input(): void
    {
        $this->unlock($this->scenario->openCode);
        $form = $this->scenario->openForm;

        $this->from('/healthform')
            ->patch(route('healthform.update', $form), [
                'submit_btn' => 'close',
                'healthform' => ['nickname' => 'Neuer Ceviname'],
                'healthinfo' => ['allergy' => 'Bienenstiche und Wespen'],
            ])
            ->assertRedirect('/healthform?form='.$form->id);

        $this->get('/healthform?form='.$form->id)
            ->assertOk()
            ->assertViewIs('healthform.edit')
            ->assertSee('Für den Abschluss brauchen wir deine Bestätigung.')
            ->assertSee('Neuer Ceviname')
            ->assertSee('Bienenstiche und Wespen');
    }

    public function test_with_two_unlocked_forms_the_failed_one_is_shown(): void
    {
        $this->unlock($this->scenario->finishedCode);
        $this->unlock($this->scenario->openCode);

        $this->from('/healthform')
            ->patch(route('healthform.update', $this->scenario->finishedForm), ['submit_btn' => 'close'])
            ->assertRedirect('/healthform?form='.$this->scenario->finishedForm->id);

        $this->get('/healthform?form='.$this->scenario->finishedForm->id)
            ->assertOk()
            ->assertViewHas('healthform', fn ($healthForm) => $healthForm->is($this->scenario->finishedForm));
    }

    public function test_form_parameter_without_unlock_redirects_to_start_page(): void
    {
        $this->get('/healthform?form='.$this->scenario->openForm->id)->assertRedirect('/');
    }

    public function test_form_parameter_of_finished_camp_redirects_to_start_page(): void
    {
        $this->unlock($this->scenario->openCode);
        $this->scenario->camp->update(['finish' => true]);

        $this->get('/healthform?form='.$this->scenario->openForm->id)->assertRedirect('/');
    }

    public function test_finished_form_is_read_only_when_camp_closes_finished_forms(): void
    {
        $this->scenario->camp->update(['forms_finished' => true]);

        $this->post('/healthform', ['camp_code' => $this->scenario->camp->code, 'code' => $this->scenario->finishedCode])
            ->assertOk()
            ->assertViewIs('healthform.show');
    }

    public function test_finished_form_stays_editable_when_camp_does_not_close_finished_forms(): void
    {
        $this->post('/healthform', ['camp_code' => $this->scenario->camp->code, 'code' => $this->scenario->finishedCode])
            ->assertOk()
            ->assertViewIs('healthform.edit');
    }

    private function unlock(int $code): void
    {
        $this->post('/healthform', ['camp_code' => $this->scenario->camp->code, 'code' => $code])->assertOk();
    }
}
