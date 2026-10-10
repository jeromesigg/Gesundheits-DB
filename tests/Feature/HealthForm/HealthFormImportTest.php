<?php

namespace Tests\Feature\HealthForm;

use App\Models\Group;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Teilnehmerliste importieren: die echte Vorlage public/files/vorlage.xlsx, ausgefüllt.
 */
class HealthFormImportTest extends TestCase
{
    private CampScenario $scenario;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
        $this->directory = sys_get_temp_dir().'/rela-import-'.uniqid();
        File::makeDirectory($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_import_creates_health_forms_with_matching_health_information(): void
    {
        $this->import([
            ['CZH', 'Muster', 'Anna', 'Fuchs', 'Weg 1', '8000', 'Zürich', Date::PHPToExcel(new \DateTime('2012-05-17')), '756.1234.5678.97'],
            ['XYZ', 'Beispiel', 'Ben', '', 'Gasse 2', '3000', 'Bern', '03.02.2011', '756.9876.5432.10'],
        ]);

        $anna = HealthForm::where('ahv', '756.1234.5678.97')->firstOrFail();
        $this->assertSame($this->scenario->camp->id, $anna->camp_id);
        $this->assertSame('Fuchs', $anna->nickname);
        $this->assertSame('2012-05-17', $anna->birthday);
        $this->assertSame(Group::where('short_name', 'CZH')->value('id'), $anna->group_id);
        $this->assertTrue(HealthInformation::where('code', $anna->code)->where('camp_id', $this->scenario->camp->id)->exists());

        $ben = HealthForm::where('ahv', '756.9876.5432.10')->firstOrFail();
        $this->assertSame('Ben', $ben->nickname);
        $this->assertSame('2011-02-03', $ben->birthday);
        $this->assertNull($ben->group_id);
        $this->assertSame('XYZ', $ben->group_text);
    }

    public function test_rows_without_ahv_or_birthday_are_skipped(): void
    {
        $count = HealthForm::count();

        $this->import([
            ['CZH', 'Ohne', 'AHV', '', '', '', '', '01.01.2012', ''],
            ['CZH', 'Ohne', 'Geburtstag', '', '', '', '', '', '756.1111.2222.33'],
        ]);

        $this->assertSame($count, HealthForm::count());
    }

    public function test_rows_with_unrecognised_birthday_are_skipped_and_reported(): void
    {
        $count = HealthForm::count();

        $this->import([
            ['CZH', 'Gut', 'Gina', '', '', '', '', '17/05/2012', '756.0000.0000.01'],
            ['CZH', 'Kurz', 'Kai', '', '', '', '', '3.2.11', '756.0000.0000.02'],
            ['CZH', 'Falsch', 'Fia', '', '', '', '', '31.02.2012', '756.0000.0000.03'],
        ])->assertSessionHas('message', fn (string $message) => str_contains($message, 'Zeilen 3, 4')
            && str_contains($message, 'TT.MM.JJJJ'));

        $this->assertSame($count + 1, HealthForm::count());
        $this->assertSame('2012-05-17', HealthForm::where('ahv', '756.0000.0000.01')->value('birthday'));
    }

    public function test_importing_the_same_list_twice_creates_nothing_new(): void
    {
        $rows = [['CZH', 'Muster', 'Anna', 'Fuchs', 'Weg 1', '8000', 'Zürich', '17.05.2012', '756.1234.5678.97']];
        $this->import($rows);
        $forms = HealthForm::count();
        $infos = HealthInformation::count();

        $this->import($rows);

        $this->assertSame($forms, HealthForm::count());
        $this->assertSame($infos, HealthInformation::count());
    }

    public function test_imported_health_forms_get_questions_of_camp(): void
    {
        $this->import([['CZH', 'Muster', 'Anna', 'Fuchs', 'Weg 1', '8000', 'Zürich', '17.05.2012', '756.1234.5678.97']]);

        $code = HealthForm::where('ahv', '756.1234.5678.97')->firstOrFail()->code;
        $healthInformation = HealthInformation::where('code', $code)->firstOrFail();
        $this->assertTrue(HealthInformationQuestion::where('health_information_id', $healthInformation->id)
            ->where('question_id', $this->scenario->question->id)->exists());
    }

    /**
     * Füllt die Vorlage ab Zeile 2 und lädt sie als Lagerleitung hoch.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function import(array $rows): TestResponse
    {
        $spreadsheet = IOFactory::load(public_path('files/vorlage.xlsx'));
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A2');
        $path = $this->directory.'/liste-'.uniqid().'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return $this->actingAs($this->scenario->manager)
            ->post('/dashboard/healthform/uploadFile', ['file' => new UploadedFile($path, 'Teilnehmerliste.xlsx', null, null, true)])
            ->assertRedirect(route('healthforms.index'));
    }
}
