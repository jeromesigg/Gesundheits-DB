<?php

namespace Tests\Feature\HealthForm;

use App\Models\Camp;
use App\Models\HealthForm;
use Tests\Support\CampScenario;
use Tests\TestCase;

class HealthFormAccessTest extends TestCase
{
    private CampScenario $scenario;

    private Camp $camp;

    private HealthForm $healthForm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
        $this->camp = $this->scenario->camp;
        $this->healthForm = $this->scenario->openForm;
    }

    public function test_guest_without_codes_cannot_show_health_form(): void
    {
        $this->get(route('healthform.show', $this->healthForm))->assertForbidden();
    }

    public function test_guest_without_codes_cannot_open_print_view(): void
    {
        $this->get(route('healthform.downloadPDF', $this->healthForm))->assertForbidden();
    }

    public function test_guest_without_codes_cannot_download_allergy_file(): void
    {
        $this->get(route('downloadAllergy', $this->healthForm))->assertForbidden();
    }

    public function test_guest_without_codes_cannot_update_health_form(): void
    {
        $nickname = $this->healthForm->nickname;

        $this->patch(route('healthform.update', $this->healthForm), $this->updatePayload('Fremd'))
            ->assertForbidden();

        $this->assertSame($nickname, $this->healthForm->fresh()->nickname);
    }

    public function test_guest_with_correct_codes_can_open_and_update_health_form(): void
    {
        $this->post('/healthform', [
            'camp_code' => $this->camp->code,
            'code' => $this->healthForm->code,
        ])->assertOk()->assertViewIs('healthform.edit');

        $this->get(route('healthform.show', $this->healthForm))->assertOk();
        $this->get(route('healthform.downloadPDF', $this->healthForm))->assertOk();

        $this->patch(route('healthform.update', $this->healthForm), $this->updatePayload('Neu'))
            ->assertRedirect('/');

        $this->assertSame('Neu', $this->healthForm->fresh()->nickname);
    }

    public function test_guest_with_wrong_personal_code_gets_no_access(): void
    {
        $this->from('/')->post('/healthform', [
            'camp_code' => $this->camp->code,
            'code' => '999999',
        ])->assertRedirect('/')->assertSessionHasErrors();

        $this->get(route('healthform.show', $this->healthForm))->assertForbidden();
    }

    public function test_personal_code_does_not_work_with_code_of_other_camp(): void
    {
        $this->from('/')->post('/healthform', [
            'camp_code' => $this->scenario->otherCamp->code,
            'code' => $this->healthForm->code,
        ])->assertRedirect('/')->assertSessionHasErrors();

        $this->get(route('healthform.show', $this->healthForm))->assertForbidden();
    }

    public function test_codes_of_one_health_form_do_not_unlock_another(): void
    {
        $otherHealthForm = $this->scenario->finishedForm;

        $this->post('/healthform', [
            'camp_code' => $this->camp->code,
            'code' => $this->healthForm->code,
        ])->assertOk();

        $this->get(route('healthform.show', $otherHealthForm))->assertForbidden();
    }

    public function test_manager_of_same_camp_can_show_health_form(): void
    {
        $this->actingAs($this->scenario->manager)->get(route('healthform.show', $this->healthForm))->assertOk();
    }

    public function test_manager_of_other_camp_cannot_show_health_form(): void
    {
        $this->actingAs($this->scenario->otherManager)->get(route('healthform.show', $this->healthForm))->assertForbidden();
    }

    public function test_helper_of_same_camp_cannot_show_health_form(): void
    {
        $this->actingAs($this->scenario->helper)->get(route('healthform.show', $this->healthForm))->assertForbidden();
    }

    public function test_entry_without_codes_redirects_to_start_page(): void
    {
        $this->get('/healthform')->assertRedirect('/');
    }

    public function test_download_allergy_without_file_returns_not_found(): void
    {
        $this->actingAs($this->scenario->manager)
            ->get(route('downloadAllergy', $this->healthForm))
            ->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(string $nickname): array
    {
        return [
            'submit_btn' => 'save',
            'healthform' => ['nickname' => $nickname],
            'healthinfo' => [],
        ];
    }
}
