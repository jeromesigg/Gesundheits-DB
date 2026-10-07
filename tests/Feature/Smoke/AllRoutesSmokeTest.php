<?php

namespace Tests\Feature\Smoke;

use App\Models\Help;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Ruft jede GET-Route der App mit jeder Rolle auf und erwartet keinen Serverfehler.
 * Neue Routen werden automatisch mitgetestet; fehlt ein Wert für einen Routenparameter,
 * schlägt der Test fehl und routeParameters() muss ergänzt werden.
 */
class AllRoutesSmokeTest extends TestCase
{
    /**
     * Routen, die hier bewusst nicht aufgerufen werden, jeweils mit Grund.
     */
    private const EXCLUDED = [
        'email/verify/{id}/{hash}' => 'signierte URL',
        'admin/run-migrations' => 'führt Migrationen aus, getestet in Security/AdminRoutesTest',
        'admin/run-deployment' => 'leert alle Caches, getestet in Security/AdminRoutesTest',
    ];

    /**
     * Bekannte, noch nicht behobene Serverfehler. Der Test bleibt grün, solange genau diese auftreten.
     * Ist einer behoben, schlägt der Test fehl, bis der Eintrag hier entfernt ist.
     */
    private const KNOWN_ISSUES = [
        // 'route/{uri}' => ['roles' => ['rolle'], 'reason' => 'Warum es (noch) kracht'],
    ];

    /**
     * Rollen wie in CampScenario::users(), plus Gast.
     *
     * @return array<string, array{string}>
     */
    public static function roles(): array
    {
        return [
            'guest' => ['guest'],
            'participant' => ['participant'],
            'helper' => ['helper'],
            'manager' => ['manager'],
            'admin' => ['admin'],
        ];
    }

    #[DataProvider('roles')]
    public function test_no_get_route_returns_a_server_error(string $role): void
    {
        $scenario = CampScenario::create();
        $user = $scenario->users()[$role] ?? null;
        $errors = [];

        foreach ($this->getRoutes() as $route) {
            foreach ($this->buildUris($route, $scenario) as $uri) {
                $error = $this->visit($uri, $user);
                if ($error) {
                    $errors[$route->uri()][] = "$uri → $error";
                }
            }
        }

        $known = array_keys(array_filter(self::KNOWN_ISSUES, fn (array $issue) => in_array($role, $issue['roles'])));
        $unexpected = array_merge([], ...array_values(array_diff_key($errors, array_flip($known))));
        $fixed = array_values(array_diff($known, array_keys($errors)));

        $this->assertSame([], $unexpected, "Neue Serverfehler als $role:\n".implode("\n", $unexpected));
        $this->assertSame([], $fixed, "Behoben als $role? Bitte aus KNOWN_ISSUES entfernen:\n".implode("\n", $fixed));
    }

    /**
     * @return string|null Fehlerbeschreibung bei Status >= 500
     */
    private function visit(string $uri, ?User $user): ?string
    {
        // Frische Instanz wie bei einem echten Request, sonst hängen geladene Relationen vom vorherigen Request am User.
        if ($user) {
            $this->actingAs($user->fresh());
        }
        $response = $this->get($uri);

        // Jeder Request startet ohne Login und Session des vorherigen.
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        if ($response->getStatusCode() < 500) {
            return null;
        }
        $exception = $response->exception;

        return $response->getStatusCode().($exception
            ? ' '.$exception->getMessage().' ('.basename($exception->getFile()).':'.$exception->getLine().')'
            : '');
    }

    /**
     * @return Route[]
     */
    private function getRoutes(): array
    {
        return collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods()))
            ->reject(fn (Route $route) => str_starts_with($route->getActionName(), 'Laravel\\')
                || str_starts_with($route->uri(), '_ignition')
                || str_starts_with($route->uri(), 'storage/')
                || $route->uri() === 'up'
                || array_key_exists($route->uri(), self::EXCLUDED))
            ->values()
            ->all();
    }

    /**
     * Jede Route mit den Standardwerten und zusätzlich mit Randfall-Objekten aus dem Szenario.
     *
     * @return string[]
     */
    private function buildUris(Route $route, CampScenario $s): array
    {
        $values = $this->routeParameters($s);
        $edgeCases = [
            'healthform' => $s->formWithoutGroup->id,
            'healthinformation' => $s->finishedInfo->code,
            'healthInformation' => $s->finishedInfo->code,
            'intervention' => $s->closedIntervention->id,
        ] + $values;

        return array_values(array_unique([
            $this->buildUri($route, $values),
            $this->buildUri($route, $edgeCases),
        ]));
    }

    /**
     * @param  array<string, string|int>  $values
     */
    private function buildUri(Route $route, array $values): string
    {
        return '/'.ltrim(preg_replace_callback('/\{(\w+)\??\}/', function ($match) use ($values, $route) {
            $this->assertArrayHasKey(
                $match[1],
                $values,
                "Kein Testwert für Parameter {{$match[1]}} der Route {$route->uri()}. Bitte in routeParameters() ergänzen."
            );

            return $values[$match[1]];
        }, $route->uri()), '/');
    }

    /**
     * Werte für Routenparameter, jeweils so, wie die App sie in Links erzeugt
     * (User per Slug, HealthInformation per Code).
     *
     * @return array<string, string|int>
     */
    private function routeParameters(CampScenario $s): array
    {
        return [
            'camp' => $s->camp->id,
            'healthform' => $s->openForm->id,
            'healthinformation' => $s->openInfo->code,
            'healthInformation' => $s->openInfo->code,
            'help' => Help::factory()->create()->id,
            'intervention' => $s->openIntervention->id,
            'question' => $s->question->id,
            'user' => $s->helper->slug,
            'token' => 'invalid-token',
        ];
    }
}
