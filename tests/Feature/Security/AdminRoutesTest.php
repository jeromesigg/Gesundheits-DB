<?php

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminRoutesTest extends TestCase
{
    /**
     * Wartungsrouten, die Artisan-Befehle auf dem Server ausführen.
     *
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function maintenanceRoutes(): array
    {
        return [
            'migrate' => ['/admin/run-migrations', 'migrate', ['--force' => true]],
            'deployment' => ['/admin/run-deployment', 'optimize:clear', []],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceRoutes')]
    public function test_guest_cannot_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        Artisan::shouldReceive('call')->never();

        $this->get($uri)->assertRedirect();
        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceRoutes')]
    public function test_manager_cannot_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        $manager = User::factory()->create(['role_id' => UserRole::CampLeader]);
        Artisan::shouldReceive('call')->never();

        $this->actingAs($manager)->get($uri)->assertRedirect('/dashboard');
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceRoutes')]
    public function test_admin_can_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        $admin = User::factory()->create(['role_id' => UserRole::Administrator]);
        Artisan::shouldReceive('call')->once()->with($command, ...($arguments === [] ? [] : [$arguments]))->andReturn(0);

        $this->actingAs($admin)->get($uri)->assertOk();
    }

    /**
     * Entfernte Routen: migrate:refresh löschte alle Daten, run-seeding legte erfundene Interventionen an.
     *
     * @return array<string, array{string}>
     */
    public static function removedRoutes(): array
    {
        return [
            'migrate refresh seed' => ['/admin/run-migrations-seed'],
            'seeding' => ['/admin/run-seeding'],
        ];
    }

    #[DataProvider('removedRoutes')]
    public function test_removed_route_does_not_exist_for_admins(string $uri): void
    {
        $admin = User::factory()->create(['role_id' => UserRole::Administrator]);
        Artisan::shouldReceive('call')->never();

        $this->actingAs($admin)->get($uri)->assertNotFound();
    }
}
