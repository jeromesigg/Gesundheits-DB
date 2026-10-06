<?php

namespace Tests\Support;

use App\Models\Camp;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use App\Models\Intervention;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

/**
 * Ein typisches, laufendes Lager mit allen Rollen und Daten, die die Seiten anzeigen,
 * plus ein zweites, fremdes Lager für Tests der Mandanten-Trennung.
 *
 * Absichtliche Randfälle: ein Gesundheitsblatt ohne Gruppe, ein abgeschlossenes Blatt,
 * eine offene und eine abgeschlossene Intervention.
 */
class CampScenario
{
    public Camp $camp;

    public User $admin;

    public User $manager;

    public User $helper;

    public User $participant;

    public Question $question;

    /** Offenes Blatt mit Antworten und Interventionen. */
    public HealthForm $openForm;

    public HealthInformation $openInfo;

    public int $openCode;

    /** Abgeschlossenes Blatt. */
    public HealthForm $finishedForm;

    public HealthInformation $finishedInfo;

    public int $finishedCode;

    /** Randfall: Blatt ohne Gruppe. */
    public HealthForm $formWithoutGroup;

    public Intervention $openIntervention;

    public Intervention $closedIntervention;

    public Camp $otherCamp;

    public User $otherManager;

    public HealthForm $otherForm;

    public HealthInformation $otherInfo;

    public int $otherCode;

    public static function create(): self
    {
        $scenario = new self;
        $scenario->build();

        return $scenario;
    }

    /**
     * Rollen-Map für Data Provider und Smoke-Tests.
     *
     * @return array<string, User>
     */
    public function users(): array
    {
        return [
            'admin' => $this->admin,
            'manager' => $this->manager,
            'helper' => $this->helper,
            'participant' => $this->participant,
        ];
    }

    /**
     * Legt ein Gesundheitsblatt an, wie HealthFormController::store() es tut: Personendaten (HealthForm,
     * Code verschlüsselt) und medizinischer Teil (HealthInformation, Code im Klartext) mit Fragen des Lagers.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{HealthForm, HealthInformation, int}
     */
    public static function createHealthForm(Camp $camp, array $attributes = []): array
    {
        $healthInformation = HealthInformation::factory()->create(['camp_id' => $camp->id]);
        $code = (int) $healthInformation->code;

        $healthForm = HealthForm::factory()->create(array_merge([
            'camp_id' => $camp->id,
            'code' => Crypt::encryptString((string) $code),
        ], $attributes));

        foreach ($camp->questions as $question) {
            HealthInformationQuestion::factory()->create([
                'health_information_id' => $healthInformation->id,
                'question_id' => $question->id,
            ]);
        }

        return [$healthForm, $healthInformation, $code];
    }

    private function build(): void
    {
        $this->camp = Camp::factory()->create();
        $this->manager = User::factory()->manager()->inCamp($this->camp)->create();
        $this->camp->update(['user_id' => $this->manager->id]);
        $this->admin = User::factory()->admin()->inCamp($this->camp)->create();
        $this->helper = User::factory()->helper()->inCamp($this->camp)->create();
        $this->participant = User::factory()->inCamp($this->camp)->create();

        $this->question = Question::factory()->create(['camp_id' => $this->camp->id, 'name' => 'Kannst du schwimmen?']);

        [$this->openForm, $this->openInfo, $this->openCode] = self::createHealthForm($this->camp);
        [$this->finishedForm, $this->finishedInfo, $this->finishedCode] = self::createHealthForm($this->camp, [
            'finish' => true,
            'date_finished' => now(),
        ]);
        [$this->formWithoutGroup] = self::createHealthForm($this->camp, ['group_id' => null, 'group_text' => null]);

        $this->openIntervention = Intervention::factory()->create([
            'health_information_id' => $this->openInfo->id,
            'user_id' => $this->helper->id,
            'user_erf' => $this->helper->username,
            'health_status_id' => config('status.health_yellow'),
        ]);
        $this->closedIntervention = Intervention::factory()->create([
            'health_information_id' => $this->openInfo->id,
            'user_id' => $this->helper->id,
            'user_erf' => $this->helper->username,
            'health_status_id' => config('status.health_green'),
            'date_close' => now()->toDateString(),
            'time_close' => now()->format('H:i'),
            'user_close' => $this->manager->username,
        ]);

        $this->otherCamp = Camp::factory()->create();
        $this->otherManager = User::factory()->manager()->inCamp($this->otherCamp)->create();
        [$this->otherForm, $this->otherInfo, $this->otherCode] = self::createHealthForm($this->otherCamp);
    }
}
