<?php

namespace Tests\Feature\Models;

use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\Intervention;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Modelle mit UUID als Primärschlüssel: Schlüssel bleiben Strings, damit Eager Loading die Einträge zuordnet.
 */
class UuidKeyTest extends TestCase
{
    public function test_uuid_models_use_string_keys(): void
    {
        $this->assertSame('string', (new HealthInformation)->getKeyType());
        $this->assertSame('string', (new HealthForm)->getKeyType());
    }

    public function test_interventions_eager_load_their_health_information(): void
    {
        $scenario = CampScenario::create();

        $intervention = Intervention::with('health_information')->findOrFail($scenario->openIntervention->id);

        $this->assertTrue($intervention->relationLoaded('health_information'));
        $this->assertSame($scenario->openInfo->id, $intervention->health_information?->id);
    }

    public function test_health_information_keeps_uuid_after_create(): void
    {
        $scenario = CampScenario::create();

        $this->assertIsString($scenario->openInfo->getKey());
        $this->assertTrue(HealthInformation::whereKey($scenario->openInfo->getKey())->exists());
    }
}
