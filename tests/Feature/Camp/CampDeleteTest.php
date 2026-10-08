<?php

namespace Tests\Feature\Camp;

use App\Models\CampUser;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\Intervention;
use Illuminate\Support\Facades\File;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Lager löschen: Gesundheitsdaten, Interventionen, Uploads und Mitgliedschaften des Lagers verschwinden,
 * das Lager bleibt als abgeschlossen mit Zähler (Statistik auf der Startseite) erhalten.
 */
class CampDeleteTest extends TestCase
{
    public function test_deleting_camp_removes_its_health_data_and_files_only(): void
    {
        $scenario = CampScenario::create();
        $storagePath = sys_get_temp_dir().'/rela-tests-'.uniqid();
        $this->app->useStoragePath($storagePath);
        $code = $scenario->camp->code;
        File::makeDirectory("$storagePath/app/files/$code", 0755, true);
        File::makeDirectory("$storagePath/app/public/files/$code", 0755, true);
        File::makeDirectory("$storagePath/app/files/{$scenario->otherCamp->code}", 0755, true);

        $this->actingAs($scenario->manager)
            ->delete(route('dashboard.camps.destroy', $scenario->camp))
            ->assertRedirect('/dashboard');

        $camp = $scenario->camp->fresh();
        $this->assertTrue($camp->finish);
        $this->assertSame(3, $camp->counter);
        $this->assertSame(0, HealthInformation::where('camp_id', $camp->id)->count());
        $this->assertSame(0, HealthForm::where('camp_id', $camp->id)->count());
        $this->assertSame(0, Intervention::where('health_information_id', $scenario->openInfo->id)->count());
        $this->assertFalse(CampUser::where('camp_id', $camp->id)->where('user_id', $scenario->helper->id)->exists());
        $this->assertDirectoryDoesNotExist("$storagePath/app/files/$code");
        $this->assertDirectoryDoesNotExist("$storagePath/app/public/files/$code");

        $this->assertTrue(HealthInformation::whereKey($scenario->otherInfo->id)->exists());
        $this->assertTrue(HealthForm::whereKey($scenario->otherForm->id)->exists());
        $this->assertDirectoryExists("$storagePath/app/files/{$scenario->otherCamp->code}");

        File::deleteDirectory($storagePath);
    }

    public function test_start_page_counts_finished_camps_and_health_forms(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager)->delete(route('dashboard.camps.destroy', $scenario->camp));
        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertViewHas('camp_counter', 1)
            ->assertViewHas('healhform_counter', 3);
    }
}
