<?php

namespace Tests\Feature\HealthForm;

use App\Enums\TrafficLight;
use App\Models\HealthForm;
use App\Models\HealthInformationQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Teilnehmende speichern und schliessen ihr Gesundheitsblatt ab (nach Eingabe der Codes).
 */
class HealthFormUpdateTest extends TestCase
{
    private CampScenario $scenario;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = sys_get_temp_dir().'/rela-tests-'.uniqid();
        File::makeDirectory($this->storagePath, 0755, true);
        $this->app->useStoragePath($this->storagePath);

        $this->scenario = CampScenario::create();
        $this->post('/healthform', ['camp_code' => $this->scenario->camp->code, 'code' => $this->scenario->openCode])->assertOk();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_save_keeps_health_form_open(): void
    {
        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('save'))
            ->assertRedirect('/');

        $healthForm = $this->freshForm();
        $this->assertSame('Neuer Name', $healthForm->nickname);
        $this->assertFalse((bool) $healthForm->finish);
    }

    public function test_close_with_privacy_agreement_finishes_health_form(): void
    {
        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('close', [
            'healthinfo' => ['allergy' => 'Nüsse', 'accept_privacy_agreement' => 1],
        ]))->assertRedirect(route('healthform.show', $this->scenario->openForm));

        $healthForm = $this->freshForm();
        $this->assertTrue((bool) $healthForm->finish);
        $this->assertNotNull($healthForm->date_finished);
        $this->assertTrue((bool) $this->scenario->openInfo->fresh()->accept_privacy_agreement);
    }

    public function test_close_without_privacy_agreement_is_rejected(): void
    {
        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('close'))
            ->assertSessionHasErrors('healthinfo.accept_privacy_agreement');

        $this->assertFalse((bool) $this->freshForm()->finish);
    }

    public function test_fields_outside_the_form_are_ignored(): void
    {
        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('save', [
            'healthform' => [
                'nickname' => 'Neuer Name',
                'camp_id' => $this->scenario->otherCamp->id,
                'code' => 'manipuliert',
                'finish' => 1,
                'file_allergies' => '../../.env',
            ],
            'healthinfo' => [
                'camp_id' => $this->scenario->otherCamp->id,
                'code' => 999999,
                'health_status_id' => TrafficLight::Red->value,
                'file_protocol' => '../../.env',
            ],
        ]));

        $healthForm = $this->freshForm();
        $this->assertSame($this->scenario->camp->id, $healthForm->camp_id);
        $this->assertSame((string) $this->scenario->openCode, (string) $healthForm->code);
        $this->assertFalse((bool) $healthForm->finish);
        $this->assertNull($healthForm->file_allergies);

        $healthInfo = $this->scenario->openInfo->fresh();
        $this->assertSame($this->scenario->camp->id, $healthInfo->camp_id);
        $this->assertSame($this->scenario->openCode, (int) $healthInfo->code);
        $this->assertSame(TrafficLight::Green, $healthInfo->health_status_id);
        $this->assertNull($healthInfo->file_protocol);
    }

    public function test_answers_only_change_questions_of_own_health_form(): void
    {
        $ownAnswer = HealthInformationQuestion::where('health_information_id', $this->scenario->openInfo->id)->firstOrFail();
        $foreignAnswer = HealthInformationQuestion::where('health_information_id', $this->scenario->finishedInfo->id)->firstOrFail();

        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('save', [
            'health_question' => [$ownAnswer->id => 'Ja', $foreignAnswer->id => 'überschrieben'],
        ]));

        $this->assertSame('Ja', $ownAnswer->fresh()->answer);
        $this->assertNull($foreignAnswer->fresh()->answer);
    }

    public function test_allergy_pass_is_stored_as_pdf_with_fixed_name(): void
    {
        $tmp = $this->storagePath.'/upload.tmp';
        file_put_contents($tmp, "%PDF-1.4\n%%EOF\n");
        $file = new UploadedFile($tmp, 'Scan vom Arzt.PDF', null, null, true);

        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('save', [
            'healthform' => ['nickname' => 'Neuer Name', 'file_allergies' => $file],
        ]));

        $path = $this->freshForm()->file_allergies;
        $this->assertStringEndsWith('/Allergiepass.pdf', $path);
        $this->assertFileExists(storage_path($path));
    }

    public function test_allergy_pass_must_be_pdf(): void
    {
        $file = UploadedFile::fake()->create('pass.html', 10, 'text/html');

        $this->patch(route('healthform.update', $this->scenario->openForm), $this->payload('save', [
            'healthform' => ['nickname' => 'Neuer Name', 'file_allergies' => $file],
        ]))->assertSessionHasErrors('healthform.file_allergies');

        $this->assertNull($this->freshForm()->file_allergies);
    }

    private function freshForm(): HealthForm
    {
        return HealthForm::findOrFail($this->scenario->openForm->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(string $button, array $overrides = []): array
    {
        return array_merge([
            'submit_btn' => $button,
            'healthform' => ['nickname' => 'Neuer Name'],
            'healthinfo' => ['allergy' => 'Nüsse'],
        ], $overrides);
    }
}
