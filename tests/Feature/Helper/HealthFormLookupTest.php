<?php

namespace Tests\Feature\Helper;

use App\Helper\Helper;
use App\Models\HealthForm;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Suche nach dem persönlichen Code: HealthInformation hat ihn im Klartext, HealthForm verschlüsselt
 * plus code_hash (HMAC) für die Suche.
 */
class HealthFormLookupTest extends TestCase
{
    public function test_health_form_and_information_are_found_by_code(): void
    {
        $scenario = CampScenario::create();

        $this->assertTrue(Helper::getHealthForm($scenario->otherCode)->is($scenario->otherForm));
        $this->assertTrue(Helper::getHealthInfo($scenario->otherCode)->is($scenario->otherInfo));
        $this->assertTrue(Helper::getHealthForm((string) $scenario->openCode)->is($scenario->openForm));
    }

    public function test_unknown_code_returns_null(): void
    {
        CampScenario::create();

        $this->assertNull(Helper::getHealthForm(111111));
        $this->assertNull(Helper::getHealthInfo(111111));
    }

    public function test_lookup_loads_only_the_matching_health_form(): void
    {
        $scenario = CampScenario::create();
        $loaded = 0;
        Event::listen('eloquent.retrieved: '.HealthForm::class, function () use (&$loaded) {
            $loaded++;
        });

        Helper::getHealthForm($scenario->openCode);

        $this->assertSame(1, $loaded);
    }

    public function test_code_hash_follows_code(): void
    {
        $scenario = CampScenario::create();
        $form = $scenario->openForm;
        $this->assertSame(HealthForm::hashCode($scenario->openCode), $form->fresh()->code_hash);

        $form->update(['code' => Crypt::encryptString('654321')]);

        $this->assertSame(HealthForm::hashCode('654321'), $form->fresh()->code_hash);
        $this->assertTrue(Helper::getHealthForm(654321)->is($form));
    }

    public function test_migration_fills_code_hash_of_existing_health_forms(): void
    {
        $scenario = CampScenario::create();
        DB::connection('mysql_info')->table('health_forms')->update(['code_hash' => null]);

        $migration = require database_path('migrations/2026_10_08_205107_add_code_hash_to_health_forms.php');
        $migration->fillCodeHashes();

        $this->assertSame(HealthForm::hashCode($scenario->openCode), $scenario->openForm->fresh()->code_hash);
        $this->assertSame(0, DB::connection('mysql_info')->table('health_forms')->whereNull('code_hash')->count());
    }
}
