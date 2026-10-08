<?php

namespace Tests\Feature\HealthForm;

use App\Exports\HealthFormsExport;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Export der Gesundheitsblätter (Teilnehmerliste) für die Lagerleitung.
 */
class HealthFormExportTest extends TestCase
{
    public function test_export_contains_only_health_forms_of_own_camp(): void
    {
        $scenario = CampScenario::create();
        Excel::fake();

        $this->actingAs($scenario->manager)->get(route('healthforms.downloadFile'))->assertOk();

        Excel::assertDownloaded('Teilnehmerliste.xlsx', function (HealthFormsExport $export) use ($scenario) {
            $ids = $export->collection()->pluck('id');

            return $ids->count() === 3
                && $ids->contains($scenario->openForm->id)
                && ! $ids->contains($scenario->otherForm->id);
        });
    }

    public function test_export_row_contains_code_names_and_status(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager);

        $row = (new HealthFormsExport($scenario->camp))->map($scenario->finishedForm->fresh());

        $this->assertSame((string) $scenario->finishedCode, (string) $row[2]);
        $this->assertSame($scenario->finishedForm->nickname, $row[3]);
        $this->assertSame('Ja', $row[8]);
    }

    public function test_helper_cannot_export(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)->get(route('healthforms.downloadFile'))->assertRedirect('/dashboard');
    }
}
