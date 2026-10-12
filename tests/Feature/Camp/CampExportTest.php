<?php

namespace Tests\Feature\Camp;

use App\Exports\CampExport;
use App\Exports\HealthFormsExport;
use App\Exports\InterventionsExport;
use App\Models\Intervention;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Excel-Export eines laufenden Lagers: Teilnehmende und Interventionen in zwei Tabellenblättern.
 */
class CampExportTest extends TestCase
{
    public function test_manager_downloads_both_sheets_of_own_camp(): void
    {
        $scenario = CampScenario::create();
        Intervention::factory()->create(['health_information_id' => $scenario->otherInfo->id]);
        Excel::fake();

        $this->actingAs($scenario->manager)
            ->get(route('dashboard.camps.export', $scenario->camp))
            ->assertOk();

        Excel::assertDownloaded('Lagerexport.xlsx', function (CampExport $export) use ($scenario) {
            [$healthForms, $interventions] = $export->sheets();
            $interventionIds = $interventions->collection()->pluck('id');

            return $healthForms instanceof HealthFormsExport
                && $healthForms->title() === 'Teilnehmende'
                && $interventions instanceof InterventionsExport
                && $interventions->title() === 'Interventionen'
                && $interventionIds->count() === 2
                && $interventionIds->contains($scenario->openIntervention->id)
                && $interventionIds->contains($scenario->closedIntervention->id);
        });
    }

    public function test_generated_file_has_both_sheets_with_rows(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager);
        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, Excel::raw(new CampExport($scenario->camp), ExcelFormat::XLSX));

        $spreadsheet = IOFactory::load($path);
        unlink($path);

        $this->assertSame(['Teilnehmende', 'Interventionen'], $spreadsheet->getSheetNames());
        $this->assertSame(4, $spreadsheet->getSheet(0)->getHighestDataRow());
        $this->assertSame(3, $spreadsheet->getSheet(1)->getHighestDataRow());
        $this->assertSame('Nr.', $spreadsheet->getSheet(1)->getCell('A1')->getValue());
    }

    public function test_intervention_row_contains_code_nickname_and_status(): void
    {
        $scenario = CampScenario::create();
        $export = new InterventionsExport($scenario->camp);
        $rows = $export->collection()->keyBy('id')->map(fn (Intervention $intervention) => $export->map($intervention));

        $open = $rows[$scenario->openIntervention->id];
        $this->assertSame((string) $scenario->openCode, (string) $open[1]);
        $this->assertSame($scenario->openForm->nickname, $open[2]);
        $this->assertSame($scenario->openIntervention->parameter, $open[5]);
        $this->assertSame('Gelb', $open[10]);
        $this->assertSame('Nein', $open[11]);

        $closed = $rows[$scenario->closedIntervention->id];
        $this->assertSame('', $closed[10]);
        $this->assertSame('Ja', $closed[11]);
        $this->assertSame($scenario->manager->username, $closed[14]);
        $this->assertCount(count($export->headings()), $closed);
    }

    public function test_sub_intervention_gets_combined_number(): void
    {
        $scenario = CampScenario::create();
        $scenario->openIntervention->update(['serial_number' => 3]);
        $sub = Intervention::factory()->create([
            'health_information_id' => $scenario->openInfo->id,
            'intervention_master_id' => $scenario->openIntervention->id,
            'serial_number' => 2,
        ]);

        $export = new InterventionsExport($scenario->camp);
        $row = $export->map($export->collection()->firstWhere('id', $sub->id));

        $this->assertSame('3.2', $row[0]);
    }

    public function test_camp_page_shows_export_button_for_running_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->get(route('dashboard.camps.index'))
            ->assertOk()
            ->assertSee(route('dashboard.camps.export', $scenario->camp));
    }

    public function test_finished_camp_cannot_be_exported(): void
    {
        $scenario = CampScenario::create();
        $scenario->camp->update(['finish' => true]);

        $this->actingAs($scenario->manager)
            ->get(route('dashboard.camps.export', $scenario->camp))
            ->assertNotFound();
    }

    public function test_manager_cannot_export_foreign_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->get(route('dashboard.camps.export', $scenario->otherCamp))
            ->assertForbidden();
    }

    public function test_helper_cannot_export(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get(route('dashboard.camps.export', $scenario->camp))
            ->assertRedirect('/dashboard');
    }
}
