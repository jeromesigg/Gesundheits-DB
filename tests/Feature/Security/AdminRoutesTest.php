<?php

namespace Tests\Feature\Security;

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
            'migrate refresh seed' => ['/admin/run-migrations-seed', 'migrate:refresh', ['--seed' => true, '--force' => true]],
            'seeding' => ['/admin/run-seeding', 'db:seed', ['--class' => 'InterventionSeeder', '--force' => true]],
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
        $manager = User::factory()->create(['role_id' => config('status.role_Lagerleiter')]);
        Artisan::shouldReceive('call')->never();

        $this->actingAs($manager)->get($uri)->assertRedirect('/dashboard');
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('maintenanceRoutes')]
    public function test_admin_can_run_maintenance_command(string $uri, string $command, array $arguments): void
    {
        $admin = User::factory()->create(['role_id' => config('status.role_Administrator')]);
        Artisan::shouldReceive('call')->once()->with($command, ...($arguments === [] ? [] : [$arguments]))->andReturn(0);

        $this->actingAs($admin)->get($uri)->assertOk();
    }
}
