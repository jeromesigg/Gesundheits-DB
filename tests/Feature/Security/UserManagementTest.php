<?php

namespace Tests\Feature\Security;

use App\Models\CampUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Leitende verwalten: nur Mitglieder des eigenen Lagers, nie Admins, Rollen nur Lagerleitung/Helfende/Teilnehmende.
 */
class UserManagementTest extends TestCase
{
    private CampScenario $scenario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
    }

    public function test_manager_can_update_member_including_password(): void
    {
        $helper = $this->scenario->helper;

        $this->actingAs($this->scenario->manager)
            ->patch(route('dashboard.users.update', $helper), $this->updatePayload($helper, [
                'password' => 'neues-passwort',
                'password_confirmation' => 'neues-passwort',
            ]))
            ->assertRedirect('/dashboard/users');

        $this->assertTrue(Hash::check('neues-passwort', $helper->fresh()->password));
    }

    public function test_manager_can_change_role_of_member_within_camp(): void
    {
        $helper = $this->scenario->helper;

        $this->actingAs($this->scenario->manager)
            ->patch(route('dashboard.users.update', $helper), $this->updatePayload($helper, ['role_id' => config('status.role_Lagerleiter')]));

        $this->assertSame(config('status.role_Lagerleiter'), $this->membership($helper)->role_id);
        $this->assertSame(config('status.role_Lagerleiter'), $helper->fresh()->role_id);
    }

    public function test_role_change_does_not_touch_current_role_in_another_camp(): void
    {
        $helper = $this->scenario->helper;
        CampUser::create(['camp_id' => $this->scenario->otherCamp->id, 'user_id' => $helper->id, 'role_id' => config('status.role_Teilnehmer'), 'active' => true]);
        $helper->update(['camp_id' => $this->scenario->otherCamp->id, 'role_id' => config('status.role_Teilnehmer')]);

        $this->actingAs($this->scenario->manager)
            ->patch(route('dashboard.users.update', $helper), $this->updatePayload($helper, ['role_id' => config('status.role_Lagerleiter')]));

        $this->assertSame(config('status.role_Lagerleiter'), $this->membership($helper)->role_id);
        $this->assertSame(config('status.role_Teilnehmer'), $helper->fresh()->role_id);
    }

    public function test_manager_cannot_grant_admin_role(): void
    {
        $helper = $this->scenario->helper;

        $this->actingAs($this->scenario->manager)
            ->patch(route('dashboard.users.update', $helper), $this->updatePayload($helper, ['role_id' => config('status.role_Administrator')]))
            ->assertSessionHasErrors('role_id');

        $this->assertSame(config('status.role_Helfer'), $helper->fresh()->role_id);
        $this->assertSame(config('status.role_Helfer'), $this->membership($helper)->role_id);
    }

    public function test_manager_cannot_edit_update_or_remove_user_of_other_camp(): void
    {
        $other = $this->scenario->otherManager;
        $this->actingAs($this->scenario->manager);

        $this->get(route('dashboard.users.edit', $other))->assertForbidden();
        $this->patch(route('dashboard.users.update', $other), $this->updatePayload($other, [
            'password' => 'gehackt12',
            'password_confirmation' => 'gehackt12',
        ]))->assertForbidden();
        $this->delete(route('dashboard.users.destroy', $other))->assertForbidden();

        $this->assertFalse(Hash::check('gehackt12', $other->fresh()->password));
        $this->assertTrue(CampUser::where('camp_id', $this->scenario->otherCamp->id)->where('user_id', $other->id)->exists());
    }

    public function test_manager_cannot_edit_or_update_admin(): void
    {
        $admin = User::findOrFail(1);
        $this->actingAs($this->scenario->manager);

        $this->get(route('dashboard.users.edit', $admin))->assertForbidden();
        $this->patch(route('dashboard.users.update', $admin), $this->updatePayload($admin, [
            'password' => 'gehackt12',
            'password_confirmation' => 'gehackt12',
        ]))->assertForbidden();
        $this->delete(route('dashboard.users.destroy', $admin))->assertForbidden();

        $this->assertFalse(Hash::check('gehackt12', $admin->fresh()->password));
    }

    public function test_manager_cannot_edit_admin_of_own_camp(): void
    {
        $this->actingAs($this->scenario->manager)
            ->get(route('dashboard.users.edit', $this->scenario->admin))
            ->assertForbidden();
    }

    public function test_manager_can_remove_member_from_camp(): void
    {
        $helper = $this->scenario->helper;

        $this->actingAs($this->scenario->manager)
            ->delete(route('dashboard.users.destroy', $helper))
            ->assertRedirect('/dashboard/users');

        $this->assertNull($this->membership($helper));
    }

    public function test_store_creates_user_with_allowed_role_only(): void
    {
        $this->actingAs($this->scenario->manager)
            ->post(route('dashboard.users.store'), [
                'username' => 'Neue Person',
                'email' => 'neu@example.com',
                'role_id' => config('status.role_Administrator'),
                'password' => 'passwort1',
                'password_confirmation' => 'passwort1',
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertFalse(User::where('email', 'neu@example.com')->exists());
    }

    public function test_store_ignores_other_fields(): void
    {
        $this->actingAs($this->scenario->manager)
            ->post(route('dashboard.users.store'), [
                'username' => 'Neue Person',
                'email' => 'neu@example.com',
                'role_id' => config('status.role_Helfer'),
                'password' => 'passwort1',
                'password_confirmation' => 'passwort1',
                'demo' => 1,
                'camp_id' => $this->scenario->otherCamp->id,
            ])
            ->assertRedirect('/dashboard/users');

        $user = User::where('email', 'neu@example.com')->firstOrFail();
        $this->assertFalse((bool) $user->demo);
        $this->assertSame($this->scenario->camp->id, $user->camp_id);
        $this->assertSame(config('status.role_Helfer'), $user->role_id);
        $this->assertSame(config('status.role_Helfer'), $this->membership($user)->role_id);
    }

    public function test_add_existing_user_with_allowed_role(): void
    {
        $person = User::factory()->create();

        $this->actingAs($this->scenario->manager)
            ->post(route('users.add'), ['user_id' => $person->id, 'role_id_add' => config('status.role_Helfer')])
            ->assertRedirect('/dashboard/users');

        $this->assertSame(config('status.role_Helfer'), $this->membership($person)->role_id);
    }

    public function test_add_cannot_grant_admin_role(): void
    {
        $person = User::factory()->create();

        $this->actingAs($this->scenario->manager)
            ->post(route('users.add'), ['user_id' => $person->id, 'role_id_add' => config('status.role_Administrator')])
            ->assertSessionHasErrors('role_id_add');

        $this->assertNull($this->membership($person));
    }

    public function test_search_finds_only_exact_email_and_returns_few_fields(): void
    {
        $person = User::factory()->create(['email' => 'person@example.com']);

        $this->actingAs($this->scenario->manager)
            ->getJson(route('searchajaxuser', ['term' => 'person@']))
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($this->scenario->manager)
            ->getJson(route('searchajaxuser', ['term' => 'PERSON@example.com']))
            ->assertOk()
            ->assertExactJson([['id' => $person->id, 'username' => $person->username, 'email' => 'person@example.com']]);
    }

    public function test_search_does_not_find_admins_or_members(): void
    {
        $this->actingAs($this->scenario->manager)
            ->getJson(route('searchajaxuser', ['term' => User::findOrFail(1)->email]))
            ->assertExactJson([]);

        $this->actingAs($this->scenario->manager)
            ->getJson(route('searchajaxuser', ['term' => $this->scenario->helper->email]))
            ->assertExactJson([]);
    }

    private function membership(User $user): ?CampUser
    {
        return CampUser::where('camp_id', $this->scenario->camp->id)->where('user_id', $user->id)->first();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => config('status.role_Helfer'),
            'active' => 1,
            'password' => '',
            'password_confirmation' => '',
        ], $overrides);
    }
}
