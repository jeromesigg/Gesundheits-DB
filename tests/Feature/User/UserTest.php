<?php

namespace Tests\Feature\User;

use Tests\Support\CampScenario;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_participant_sees_health_form_with_correct_codes(): void
    {
        $scenario = CampScenario::create();

        $this->post('/healthform', [
            'code' => $scenario->openCode,
            'camp_code' => $scenario->camp->code,
        ])->assertOk()
            ->assertViewIs('healthform.edit')
            ->assertSee($scenario->openForm->nickname);
    }

    public function test_participant_cannot_open_health_form_of_finished_camp(): void
    {
        $scenario = CampScenario::create();
        $scenario->camp->update(['finish' => true]);

        $this->from('/')->post('/healthform', [
            'code' => $scenario->openCode,
            'camp_code' => $scenario->camp->code,
        ])->assertRedirect('/')->assertSessionHasErrors();
    }
}
