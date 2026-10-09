<?php

namespace Tests\Feature\HealthInformation;

use App\Models\HealthForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Support\CampScenario;
use Tests\TestCase;

class HealthInformationRequestTest extends TestCase
{
    public function test_search_without_code_redirects_back_with_error(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->from('/dashboard')
            ->get('/healthinformation/search')
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors();
    }

    public function test_search_with_code_of_own_camp_opens_health_information(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get('/healthinformation/search?code='.$scenario->openCode)
            ->assertRedirect(route('healthinformation.show', $scenario->openInfo));
    }

    public function test_search_with_code_of_other_camp_finds_nothing(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->from('/dashboard')
            ->get('/healthinformation/search?code='.$scenario->otherCode)
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors();
    }

    public function test_download_protocol_without_file_returns_not_found(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->get('/healthinformation/downloadProtocol/'.$scenario->openInfo->code)
            ->assertNotFound();
    }

    public function test_protocol_is_stored_as_pdf_with_fixed_name_and_can_be_downloaded(): void
    {
        $scenario = CampScenario::create();
        $storagePath = $this->useTemporaryStorage();
        $tmp = $storagePath.'/upload.tmp';
        file_put_contents($tmp, "%PDF-1.4\n%%EOF\n");

        $this->actingAs($scenario->manager)
            ->post(route('uploadProtocol', $scenario->openInfo), [
                'file_protocol' => new UploadedFile($tmp, 'Notfall Scan.PDF', null, null, true),
            ])
            ->assertRedirect(route('healthinformation.show', $scenario->openInfo));

        $path = $scenario->openInfo->fresh()->file_protocol;
        $this->assertStringEndsWith('/Notfallblatt.pdf', $path);
        $this->actingAs($scenario->manager)->get(route('downloadProtocol', $scenario->openInfo))->assertOk();

        File::deleteDirectory($storagePath);
    }

    public function test_protocol_must_be_pdf(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->post(route('uploadProtocol', $scenario->openInfo), [
                'file_protocol' => UploadedFile::fake()->create('protokoll.html', 5, 'text/html'),
            ])
            ->assertSessionHasErrors('file_protocol');

        $this->assertNull($scenario->openInfo->fresh()->file_protocol);
    }

    public function test_participant_list_upload_must_be_spreadsheet(): void
    {
        $scenario = CampScenario::create();
        $count = HealthForm::count();

        $this->actingAs($scenario->manager)
            ->post('/dashboard/healthform/uploadFile', [
                'file' => UploadedFile::fake()->create('liste.html', 5, 'text/html'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame($count, HealthForm::count());
    }

    private function useTemporaryStorage(): string
    {
        $storagePath = sys_get_temp_dir().'/rela-tests-'.uniqid();
        File::makeDirectory($storagePath, 0755, true);
        $this->app->useStoragePath($storagePath);

        return $storagePath;
    }
}
