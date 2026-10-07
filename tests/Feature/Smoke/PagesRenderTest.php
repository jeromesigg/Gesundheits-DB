<?php

namespace Tests\Feature\Smoke;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Alle Seiten, die Navigation und Dashboard einer Rolle verlinken, laden für diese Rolle mit 200.
 */
class PagesRenderTest extends TestCase
{
    /**
     * @return array<string, array{string, Closure(CampScenario): string}>
     */
    public static function linkedPages(): array
    {
        $pages = [];
        $common = [
            'Dashboard' => fn (CampScenario $s) => '/dashboard',
        ];
        $helper = [
            'Gesundheitsblätter' => fn (CampScenario $s) => route('healthforms.index'),
            'Teilnehmerübersicht' => fn (CampScenario $s) => route('healthinformation.index'),
            'Alle Interventionen' => fn (CampScenario $s) => route('interventions.index'),
            'Patientenakte' => fn (CampScenario $s) => route('healthinformation.show', $s->openInfo),
            'Patientenakte ohne Interventionen' => fn (CampScenario $s) => route('healthinformation.show', $s->finishedInfo),
            'Intervention bearbeiten' => fn (CampScenario $s) => route('interventions.edit', $s->openIntervention),
            'Intervention abschliessen' => fn (CampScenario $s) => route('interventions.close', $s->openIntervention),
        ];
        $manager = [
            'Leiter' => fn (CampScenario $s) => route('dashboard.users.index'),
            'Fragen' => fn (CampScenario $s) => route('dashboard.questions.index'),
            'Lager' => fn (CampScenario $s) => route('dashboard.camps.index'),
            'Gesundheitsblatt erstellen' => fn (CampScenario $s) => route('healthforms.create'),
            'Gesundheitsblatt bearbeiten' => fn (CampScenario $s) => route('healthforms.showOrEdit', $s->openForm),
            'Gesundheitsblatt abgeschlossen' => fn (CampScenario $s) => route('healthforms.showOrEdit', $s->finishedForm),
            'Gesundheitsblatt ohne Gruppe' => fn (CampScenario $s) => route('healthforms.showOrEdit', $s->formWithoutGroup),
        ];
        $admin = [
            'Audit-Trail' => fn (CampScenario $s) => route('dashboard.audits'),
            'Hilfe-Artikel' => fn (CampScenario $s) => route('helps.index'),
        ];
        $rolePages = [
            'participant' => $common,
            'helper' => $common + $helper,
            'manager' => $common + $helper + $manager,
            'admin' => $common + $helper + $manager + $admin,
        ];
        foreach ($rolePages as $role => $rolePagesOfRole) {
            foreach ($rolePagesOfRole as $name => $uri) {
                $pages["$role: $name"] = [$role, $uri];
            }
        }

        return $pages;
    }

    #[DataProvider('linkedPages')]
    public function test_linked_page_renders_for_role(string $role, Closure $uri): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->users()[$role])
            ->get($uri($scenario))
            ->assertOk();
    }

    /**
     * Navigation: jede Rolle sieht genau die Menüpunkte, die sie öffnen darf.
     *
     * @return array<string, array{string, array<int, string>, array<int, string>}>
     */
    public static function navigation(): array
    {
        $helper = ['Gesundheitsblätter', 'Teilnehmerübersicht', 'Alle Interventionen'];
        $managerLinks = ['>Leiter<', '>Fragen<', '>Lager<'];
        $adminLinks = ['Audit-Trail', 'Hilfe-Artikel'];

        return [
            'participant' => ['participant', [], [...$helper, ...$managerLinks, ...$adminLinks]],
            'helper' => ['helper', $helper, [...$managerLinks, ...$adminLinks]],
            'manager' => ['manager', [...$helper, ...$managerLinks], $adminLinks],
            'admin' => ['admin', [...$helper, ...$managerLinks, ...$adminLinks], []],
        ];
    }

    /**
     * @param  array<int, string>  $visible
     * @param  array<int, string>  $hidden
     */
    #[DataProvider('navigation')]
    public function test_navigation_shows_only_allowed_entries(string $role, array $visible, array $hidden): void
    {
        $scenario = CampScenario::create();

        $response = $this->actingAs($scenario->users()[$role])->get('/dashboard')->assertOk();

        foreach ($visible as $text) {
            $response->assertSee($text, false);
        }
        foreach ($hidden as $text) {
            $response->assertDontSee($text, false);
        }
    }

    public function test_dashboard_hides_health_record_search_from_participants(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->participant)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Patientenakte öffnen')
            ->assertSee('keinen Zugriff auf Gesundheitsdaten');
    }

    public function test_dashboard_shows_health_record_search_to_helpers(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Patientenakte öffnen');
    }

    /**
     * Der Cevi-DB-Import war nie angeschlossen ($has_api_token fehlte) und ist entfernt.
     * Auch mit der alten Einstellung IMPORT_DB=true muss die Leiter-Seite laden.
     */
    public function test_users_page_has_no_cevi_db_import(): void
    {
        $scenario = CampScenario::create();
        config(['app.import_db' => true]);

        $this->actingAs($scenario->manager)
            ->get(route('dashboard.users.index'))
            ->assertOk()
            ->assertDontSee('Personen aus Cevi-DB importieren');
    }
}
