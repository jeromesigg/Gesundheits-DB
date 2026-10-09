<?php

namespace Tests\Feature\Security;

use App\Models\HealthForm;
use Illuminate\Support\Facades\File;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Downloads lesen nur Dateien aus dem Upload-Ordner storage/app/files, Pfade kommen nie aus Formularen.
 */
class FileAccessTest extends TestCase
{
    public function test_create_health_form_ignores_fields_outside_the_form(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)->post(route('healthforms.store'), [
            'nickname' => 'Neu',
            'first_name' => 'Neue',
            'last_name' => 'Person',
            'group_text' => '',
            'file_allergies' => '../composer.json',
            'finish' => 1,
            'camp_id' => $scenario->otherCamp->id,
        ])->assertOk();

        $healthForm = HealthForm::where('nickname', 'Neu')->firstOrFail();
        $this->assertNull($healthForm->file_allergies);
        $this->assertFalse((bool) $healthForm->finish);
        $this->assertSame($scenario->camp->id, $healthForm->camp_id);
    }

    public function test_allergy_download_refuses_path_outside_upload_folder(): void
    {
        $scenario = CampScenario::create();
        $this->useStorageWithSecretNextToUploads();
        $scenario->openForm->update(['file_allergies' => 'app/files/../geheim.txt']);

        $this->actingAs($scenario->manager)
            ->get(route('downloadAllergy', $scenario->openForm))
            ->assertNotFound();
    }

    public function test_protocol_download_refuses_path_outside_upload_folder(): void
    {
        $scenario = CampScenario::create();
        $this->useStorageWithSecretNextToUploads();
        $scenario->openInfo->update(['file_protocol' => 'app/files/../geheim.txt']);

        $this->actingAs($scenario->manager)
            ->get(route('downloadProtocol', $scenario->openInfo))
            ->assertNotFound();
    }

    public function test_download_of_file_in_upload_folder_works(): void
    {
        $scenario = CampScenario::create();
        $storagePath = $this->useStorageWithSecretNextToUploads();
        file_put_contents($storagePath.'/app/files/Allergiepass.pdf', '%PDF-1.4');
        $scenario->openForm->update(['file_allergies' => 'app/files/Allergiepass.pdf']);

        $this->actingAs($scenario->manager)
            ->get(route('downloadAllergy', $scenario->openForm))
            ->assertOk();
    }

    protected function tearDown(): void
    {
        if (isset($this->storagePath)) {
            File::deleteDirectory($this->storagePath);
        }

        parent::tearDown();
    }

    private ?string $storagePath = null;

    /**
     * Temporärer Speicher mit Upload-Ordner app/files und einer Datei ausserhalb davon.
     */
    private function useStorageWithSecretNextToUploads(): string
    {
        $this->storagePath = sys_get_temp_dir().'/rela-tests-'.uniqid();
        File::makeDirectory($this->storagePath.'/app/files', 0755, true);
        file_put_contents($this->storagePath.'/app/geheim.txt', 'geheim');
        $this->app->useStoragePath($this->storagePath);

        return $this->storagePath;
    }
}
