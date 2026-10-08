<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Http;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Entfernte, nie fertig gewordene Funktionen bleiben entfernt.
 */
class RemovedFeaturesTest extends TestCase
{
    /**
     * Der Cevi-DB-Import der Gesundheitsblätter rief mit dem Server-Token db.cevi.ch auf
     * und legte Blätter ohne Lager an; jede Lagerleitung konnte ihn auslösen.
     */
    public function test_cevi_db_import_of_health_forms_is_removed(): void
    {
        $scenario = CampScenario::create();
        Http::preventStrayRequests();

        $this->actingAs($scenario->manager)
            ->post('/dashboard/healthforms/import')
            ->assertNotFound();
    }

    public function test_health_form_list_has_no_cevi_db_import(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->get(route('healthforms.index'))
            ->assertOk()
            ->assertDontSee('aus Cevi-DB importieren');
    }
}
