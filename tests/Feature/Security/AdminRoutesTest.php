<?php

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Wartung (Migrationen, Caches) über das Admin-Dashboard: nur Admins, Aktionen nur per POST (CSRF-geschützt).
 * Artisan ist gefakt, damit nichts wirklich migriert oder geleert wird.
 */
class AdminRoutesTest extends TestCase
{
    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function maintenanceActions(): array
    {
        return [
            'Migrationen' => ['/admin/maintenance/migrate', 'migrate', ['--force' => true]],
            'Caches leeren' => ['/admin/maintenance/clear-cache', 'optimize:clear', []],
        ];
    }

    public function test_admin_sees_maintenance_page_with_pending_migrations(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.maintenance'))
            ->assertOk()
            ->assertViewHas('pendingMigrations', [])
            ->assertSee('Keine offenen Migrationen');
    }

    public function test_admin_navigation_links_to_maintenance(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertSee(route('admin.maintenance'), false);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceActions')]
    public function test_admin_can_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        Artisan::shouldReceive('call')->once()->with($command, ...($arguments === [] ? [] : [$arguments]))->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('Ausgabe des Befehls');

        $this->actingAs($this->admin())
            ->post($uri)
            ->assertRedirect(route('admin.maintenance'))
            ->assertSessionHas('maintenance_output', 'Ausgabe des Befehls');
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceActions')]
    public function test_guest_cannot_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        Artisan::shouldReceive('call')->never();

        $this->post($uri)->assertRedirect();
        $this->get(route('admin.maintenance'))->assertRedirect();
        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceActions')]
    public function test_manager_cannot_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        $manager = User::factory()->create(['role_id' => UserRole::CampLeader]);
        Artisan::shouldReceive('call')->never();

        $this->actingAs($manager)->post($uri)->assertRedirect('/dashboard');
        $this->actingAs($manager)->get(route('admin.maintenance'))->assertRedirect('/dashboard');
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceActions')]
    public function test_maintenance_actions_are_not_reachable_by_get(string $uri, string $command, array $arguments): void
    {
        Artisan::shouldReceive('call')->never();

        $this->actingAs($this->admin())->get($uri)->assertMethodNotAllowed();
    }

    /**
     * Frühere GET-Routen: per Link oder Bild aus fremden Seiten auslösbar, migrate:refresh löschte alle Daten.
     *
     * @return array<string, array{string}>
     */
    public static function removedRoutes(): array
    {
        return [
            'migrate' => ['/admin/run-migrations'],
            'deployment' => ['/admin/run-deployment'],
            'migrate refresh seed' => ['/admin/run-migrations-seed'],
            'seeding' => ['/admin/run-seeding'],
        ];
    }

    #[DataProvider('removedRoutes')]
    public function test_removed_route_does_not_exist_for_admins(string $uri): void
    {
        Artisan::shouldReceive('call')->never();

        $this->actingAs($this->admin())->get($uri)->assertNotFound();
    }

    private function admin(): User
    {
        return User::factory()->create(['role_id' => UserRole::Administrator]);
    }
}
