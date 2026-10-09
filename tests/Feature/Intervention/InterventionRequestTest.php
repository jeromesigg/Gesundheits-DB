<?php

namespace Tests\Feature\Intervention;

use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * AJAX-Endpunkte der Interventionen mit fehlenden oder unvollständigen Parametern.
 */
class InterventionRequestTest extends TestCase
{
    public function test_data_tables_work_without_filter_parameters(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->getJson('/interventions/createDataTables')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2);
    }

    public function test_data_tables_filter_master_interventions_of_one_health_information(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->getJson('/interventions/createDataTables?filter=master&info='.$scenario->openInfo->id)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2);
    }

    public function test_add_new_without_parameters_is_rejected(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->getJson('/interventions/addNew')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['healthInformation_id', 'index']);
    }

    public function test_add_new_renders_form_row(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get('/interventions/addNew?index=1&healthInformation_id='.$scenario->openInfo->id)
            ->assertOk();
    }

    public function test_close_ajax_renders_close_form(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get('/interventions/closeAjax')
            ->assertOk();
    }
}
