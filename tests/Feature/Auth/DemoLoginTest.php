<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DemoCampSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function demoLogins(): array
    {
        return [
            'Leiter' => ['/loginLeiter', 'leiter1@demo'],
            'Lagerleiter' => ['/loginLagerleiter', 'lagerleiter@demo'],
        ];
    }

    #[DataProvider('demoLogins')]
    public function test_demo_login_logs_in_demo_user(string $uri, string $username): void
    {
        $this->seed(DemoCampSeeder::class);

        $this->get($uri)->assertRedirect('dashboard');

        $user = User::where('username', $username)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue((bool) $user->demo);
    }

    #[DataProvider('demoLogins')]
    public function test_demo_login_returns_not_found_without_demo_users(string $uri, string $username): void
    {
        $this->get($uri)->assertNotFound();
        $this->assertGuest();
        $this->assertFalse(User::where('username', $username)->exists());
    }
}
