<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Profil: jede Person ändert nur das eigene Konto und nur E-Mail und Passwort.
 */
class ProfileTest extends TestCase
{
    public function test_user_can_change_own_password_and_email(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->patch(route('dashboard.update', $scenario->helper), [
                'email' => 'neu@example.com',
                'password' => 'neues-passwort',
                'password_confirmation' => 'neues-passwort',
            ])
            ->assertRedirect('/dashboard');

        $helper = $scenario->helper->fresh();
        $this->assertSame('neu@example.com', $helper->email);
        $this->assertTrue(Hash::check('neues-passwort', $helper->password));
    }

    public function test_user_can_change_email_without_password(): void
    {
        $scenario = CampScenario::create();
        $passwordHash = $scenario->helper->password;

        $this->actingAs($scenario->helper)
            ->patch(route('dashboard.update', $scenario->helper), ['email' => 'neu@example.com', 'password' => ''])
            ->assertRedirect('/dashboard');

        $this->assertSame($passwordHash, $scenario->helper->fresh()->password);
    }

    public function test_user_cannot_change_password_of_another_user(): void
    {
        $stranger = User::factory()->create();
        $admin = User::findOrFail(1);

        $this->actingAs($stranger)
            ->patch(route('dashboard.update', $admin), [
                'email' => $admin->email,
                'password' => 'gehackt12',
                'password_confirmation' => 'gehackt12',
            ])
            ->assertForbidden();

        $this->assertFalse(Hash::check('gehackt12', $admin->fresh()->password));
    }

    public function test_user_cannot_change_own_role_camp_or_demo_flag(): void
    {
        $scenario = CampScenario::create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->patch(route('dashboard.update', $stranger), [
                'email' => $stranger->email,
                'password' => '',
                'role_id' => config('status.role_Administrator'),
                'camp_id' => $scenario->camp->id,
                'demo' => 1,
            ]);

        $stranger = $stranger->fresh();
        $this->assertSame(config('status.role_Teilnehmer'), $stranger->role_id);
        $this->assertSame(1, $stranger->camp_id);
        $this->assertFalse((bool) $stranger->demo);
    }

    public function test_password_must_be_confirmed(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->patch(route('dashboard.update', $scenario->helper), [
                'email' => $scenario->helper->email,
                'password' => 'neues-passwort',
                'password_confirmation' => 'anders',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_profile_form_posts_to_own_profile_route(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get(route('dashboard.user', $scenario->helper))
            ->assertOk()
            ->assertSee('action="'.route('dashboard.update', $scenario->helper).'"', false);
    }
}
