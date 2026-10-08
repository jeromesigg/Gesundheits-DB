<?php

namespace Tests\Feature\Intervention;

use App\Models\Intervention;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Interventionen speichern: nur Formularfelder, Nummern und Zuordnung bestimmt der Server.
 */
class InterventionStoreTest extends TestCase
{
    private CampScenario $scenario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
    }

    public function test_new_intervention_gets_next_serial_number_of_camp(): void
    {
        $this->scenario->camp->update(['max_serial_number' => 7]);

        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), $this->payload([
                'id' => 999,
                'serial_number' => 1,
                'max_serial_number' => 50,
                'user_id' => $this->scenario->manager->id,
            ]))
            ->assertRedirect(route('healthinformation.show', $this->scenario->openInfo));

        $intervention = Intervention::latest('id')->firstOrFail();
        $this->assertNotSame(999, $intervention->id);
        $this->assertSame(8, $intervention->serial_number);
        $this->assertNull($intervention->max_serial_number);
        $this->assertSame($this->scenario->helper->id, $intervention->user_id);
        $this->assertSame(8, $this->scenario->camp->fresh()->max_serial_number);
    }

    public function test_follow_up_interventions_stay_with_own_health_information(): void
    {
        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), $this->payload([
                'intervention_new' => [[
                    'date' => now()->toDateString(),
                    'time' => '10:00',
                    'parameter' => 'Kontrolle',
                    'user_erf' => $this->scenario->helper->username,
                    'health_information_id' => $this->scenario->otherInfo->id,
                    'intervention_master_id' => $this->scenario->closedIntervention->id,
                    'serial_number' => 77,
                ]],
            ]));

        $followUp = Intervention::where('parameter', 'Kontrolle')->firstOrFail();
        $this->assertSame($this->scenario->openInfo->id, $followUp->health_information_id);
        $this->assertNotSame(77, $followUp->serial_number);
    }

    public function test_sub_intervention_cannot_be_moved_to_other_health_information(): void
    {
        $sub = $this->scenario->closedIntervention;

        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), $this->payload([
                'intervention_sub' => [[
                    'intervention_id' => $sub->id,
                    'parameter' => 'geändert',
                    'health_information_id' => $this->scenario->otherInfo->id,
                    'serial_number' => 55,
                ]],
            ]));

        $sub = $sub->fresh();
        $this->assertSame('geändert', $sub->parameter);
        $this->assertSame($this->scenario->openInfo->id, $sub->health_information_id);
        $this->assertNotSame(55, $sub->serial_number);
    }

    public function test_uploaded_picture_is_stored_in_folder_of_health_information(): void
    {
        $storagePath = sys_get_temp_dir().'/rela-tests-'.uniqid();
        $this->app->useStoragePath($storagePath);

        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), $this->payload([
                'file' => UploadedFile::fake()->image('Foto vom Knie.jpg', 40, 40),
            ]));

        $file = Intervention::latest('id')->firstOrFail()->file;
        $this->assertStringStartsWith('files/'.$this->scenario->camp->code.'/'.$this->scenario->openCode.'/', $file);
        $this->assertStringEndsWith('_FotovomKnie.jpg', $file);
        $this->assertFileExists($storagePath.'/app/public/'.$file);

        File::deleteDirectory($storagePath);
    }

    public function test_file_path_from_form_is_ignored(): void
    {
        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), $this->payload(['file' => '../../.env']));

        $this->assertNull(Intervention::latest('id')->firstOrFail()->file);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'health_information_id' => $this->scenario->openInfo->id,
            'date' => now()->toDateString(),
            'time' => now()->format('H:i'),
            'parameter' => 'Kopfweh',
            'value' => 'leicht',
            'user_erf' => $this->scenario->helper->username,
            'health_status_id' => config('status.health_green'),
        ], $overrides);
    }
}
