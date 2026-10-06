<?php

namespace Tests\Feature\Security;

use App\Models\HealthForm;
use App\Models\Intervention;
use App\Models\User;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Gesundheitsinfos, Interventionen und Gesundheitsblätter:
 * Teilnehmende (eingeloggt) sehen nichts davon, Helfende und Lagerleitung nur das eigene Lager.
 */
class CampAccessTest extends TestCase
{
    private CampScenario $scenario;

    private Intervention $otherIntervention;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
        $this->otherIntervention = Intervention::factory()->create([
            'health_information_id' => $this->scenario->otherInfo->id,
            'user_id' => $this->scenario->otherManager->id,
            'user_erf' => $this->scenario->otherManager->username,
        ]);
    }

    /**
     * Seiten mit Gesundheitsdaten des eigenen Lagers.
     *
     * @return array<string, array{Closure(CampScenario): string}>
     */
    public static function helperPagesOfOwnCamp(): array
    {
        return [
            'Teilnehmerübersicht' => [fn (CampScenario $s) => '/dashboard/healthinformation'],
            'Teilnehmerliste Daten' => [fn (CampScenario $s) => '/healthinformation/createDataTables'],
            'Gesundheitsinfo' => [fn (CampScenario $s) => '/dashboard/healthinformation/'.$s->openInfo->code],
            'Interventionen' => [fn (CampScenario $s) => '/dashboard/interventions'],
            'Interventionen Daten' => [fn (CampScenario $s) => '/interventions/createDataTables?filter=all&info='],
            'Intervention bearbeiten' => [fn (CampScenario $s) => '/dashboard/interventions/'.$s->openIntervention->id.'/edit'],
            'Intervention abschliessen' => [fn (CampScenario $s) => '/interventions/close/'.$s->openIntervention->id],
            'Folge-Intervention' => [fn (CampScenario $s) => '/healthinformation/'.$s->openInfo->code.'/intervention/'.$s->openIntervention->id.'/createNew'],
            'Gesundheitsblätter' => [fn (CampScenario $s) => '/dashboard/healthforms'],
            'Gesundheitsblätter Daten' => [fn (CampScenario $s) => '/healthforms/createDataTables'],
            'Code-Suche' => [fn (CampScenario $s) => '/healthinformation/searchajaxcode?term='.substr((string) $s->openCode, 0, 3)],
        ];
    }

    /**
     * Seiten mit Gesundheitsdaten eines fremden Lagers.
     *
     * @return array<string, array{Closure(CampScenario, Intervention): string}>
     */
    public static function pagesOfOtherCamp(): array
    {
        return [
            'Gesundheitsinfo' => [fn (CampScenario $s, Intervention $i) => '/dashboard/healthinformation/'.$s->otherInfo->code],
            'Intervention bearbeiten' => [fn (CampScenario $s, Intervention $i) => '/dashboard/interventions/'.$i->id.'/edit'],
            'Intervention abschliessen' => [fn (CampScenario $s, Intervention $i) => '/interventions/close/'.$i->id],
            'Folge-Intervention' => [fn (CampScenario $s, Intervention $i) => '/healthinformation/'.$s->otherInfo->code.'/intervention/'.$i->id.'/createNew'],
            'Folge-Intervention fremder Master' => [fn (CampScenario $s, Intervention $i) => '/healthinformation/'.$s->openInfo->code.'/intervention/'.$i->id.'/createNew'],
            'Druckansicht' => [fn (CampScenario $s, Intervention $i) => '/healthinformation/print/'.$s->otherInfo->code],
            'Protokoll' => [fn (CampScenario $s, Intervention $i) => '/healthinformation/downloadProtocol/'.$s->otherInfo->code],
            'Gesundheitsblatt' => [fn (CampScenario $s, Intervention $i) => '/dashboard/healthforms/showOrEdit/'.$s->otherForm->id],
        ];
    }

    #[DataProvider('helperPagesOfOwnCamp')]
    public function test_participant_cannot_open_health_data_pages(Closure $uri): void
    {
        $this->actingAs($this->scenario->participant)
            ->get($uri($this->scenario))
            ->assertRedirect();
    }

    #[DataProvider('helperPagesOfOwnCamp')]
    public function test_helper_can_open_health_data_pages_of_own_camp(Closure $uri): void
    {
        $this->actingAs($this->scenario->helper)
            ->get($uri($this->scenario))
            ->assertOk();
    }

    #[DataProvider('pagesOfOtherCamp')]
    public function test_manager_cannot_open_pages_of_other_camp(Closure $uri): void
    {
        $this->actingAs($this->scenario->manager)
            ->get($uri($this->scenario, $this->otherIntervention))
            ->assertForbidden();
    }

    #[DataProvider('pagesOfOtherCamp')]
    public function test_admin_cannot_open_pages_of_other_camp(Closure $uri): void
    {
        $this->actingAs($this->scenario->admin)
            ->get($uri($this->scenario, $this->otherIntervention))
            ->assertForbidden();
    }

    public function test_helper_cannot_open_health_information_of_other_camp(): void
    {
        $this->actingAs($this->scenario->helper)
            ->get('/dashboard/healthinformation/'.$this->scenario->otherInfo->code)
            ->assertForbidden();
    }

    public function test_user_without_camp_cannot_open_health_information(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get('/dashboard/healthinformation/'.$this->scenario->otherInfo->code)
            ->assertRedirect();
    }

    public function test_code_search_only_returns_codes_of_own_camp(): void
    {
        $response = $this->actingAs($this->scenario->helper)
            ->getJson('/healthinformation/searchajaxcode?term='.$this->scenario->otherCode)
            ->assertOk();

        $this->assertSame([], $response->json());
    }

    public function test_code_search_returns_only_the_code(): void
    {
        $response = $this->actingAs($this->scenario->helper)
            ->getJson('/healthinformation/searchajaxcode?term='.$this->scenario->openCode)
            ->assertOk();

        $this->assertSame([['code' => $this->scenario->openCode]], $response->json());
    }

    public function test_helper_cannot_store_intervention_for_other_camp(): void
    {
        $count = Intervention::count();

        $this->actingAs($this->scenario->helper)
            ->post('/dashboard/interventions', $this->interventionPayload([
                'health_information_id' => $this->scenario->otherInfo->id,
            ]))
            ->assertForbidden();

        $this->assertSame($count, Intervention::count());
    }

    public function test_helper_cannot_update_intervention_of_other_camp_via_own_health_information(): void
    {
        $this->actingAs($this->scenario->helper)
            ->post('/dashboard/interventions', $this->interventionPayload([
                'intervention_id' => $this->otherIntervention->id,
                'parameter' => 'überschrieben',
            ]))
            ->assertForbidden();

        $this->assertNotSame('überschrieben', $this->otherIntervention->fresh()->parameter);
        $this->assertSame($this->scenario->otherInfo->id, $this->otherIntervention->fresh()->health_information_id);
    }

    public function test_helper_cannot_add_follow_up_to_intervention_of_other_camp(): void
    {
        $count = Intervention::count();

        $this->actingAs($this->scenario->helper)
            ->post('/dashboard/interventions', $this->interventionPayload([
                'intervention_master_id' => $this->otherIntervention->id,
            ]))
            ->assertForbidden();

        $this->assertSame($count, Intervention::count());
    }

    public function test_helper_cannot_update_sub_intervention_of_other_camp(): void
    {
        $this->actingAs($this->scenario->helper)
            ->post('/dashboard/interventions', $this->interventionPayload([
                'intervention_sub' => [['intervention_id' => $this->otherIntervention->id, 'parameter' => 'überschrieben']],
            ]))
            ->assertForbidden();

        $this->assertNotSame('überschrieben', $this->otherIntervention->fresh()->parameter);
    }

    public function test_helper_can_store_intervention_for_own_camp(): void
    {
        $count = Intervention::count();

        $this->actingAs($this->scenario->helper)
            ->post('/dashboard/interventions', $this->interventionPayload())
            ->assertRedirect(route('healthinformation.show', $this->scenario->openInfo));

        $this->assertSame($count + 1, Intervention::count());
    }

    public function test_manager_cannot_reopen_health_form_of_other_camp(): void
    {
        $this->scenario->otherForm->update(['finish' => true]);

        $this->actingAs($this->scenario->manager)
            ->post('/healthforms/'.$this->scenario->otherForm->id.'/open')
            ->assertForbidden();

        $this->assertTrue((bool) HealthForm::findOrFail($this->scenario->otherForm->id)->finish);
    }

    public function test_manager_cannot_upload_protocol_for_other_camp(): void
    {
        $this->actingAs($this->scenario->manager)
            ->post('/dashboard/healthinformation/uploadProtocol/'.$this->scenario->otherInfo->code)
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function interventionPayload(array $overrides = []): array
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
