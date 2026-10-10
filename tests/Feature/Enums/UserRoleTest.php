<?php

namespace Tests\Feature\Enums;

use App\Enums\UserRole;
use App\Models\CampUser;
use App\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Rollen: Enum-Werte = IDs der Tabelle roles, Berechtigungen aus dem Enum.
 */
class UserRoleTest extends TestCase
{
    public function test_enum_values_match_roles_table(): void
    {
        $this->assertSame(
            Role::orderBy('id')->pluck('id')->all(),
            array_map(fn (UserRole $role) => $role->value, UserRole::cases())
        );
    }

    /**
     * Die Flags in der Tabelle roles müssen zu den Regeln im Enum passen.
     */
    public function test_role_table_flags_match_enum_rules(): void
    {
        foreach (Role::all() as $role) {
            $userRole = UserRole::from($role->id);
            $this->assertSame($role->is_admin, $userRole === UserRole::Administrator, $role->name);
            $this->assertSame($role->is_admin || $role->is_manager, $userRole->canManage(), $role->name);
            $this->assertSame($role->is_admin || $role->is_manager || $role->is_helper, $userRole->canHelp(), $role->name);
        }
    }

    /**
     * @return array<string, array{UserRole, bool, bool, bool}>
     */
    public static function permissions(): array
    {
        return [
            'Administrator' => [UserRole::Administrator, true, true, true],
            'Lagerleitung' => [UserRole::CampLeader, false, true, true],
            'Helfende' => [UserRole::Helper, false, false, true],
            'Teilnehmende' => [UserRole::Participant, false, false, false],
        ];
    }

    #[DataProvider('permissions')]
    public function test_user_permissions_follow_role(UserRole $role, bool $admin, bool $manager, bool $helper): void
    {
        $scenario = CampScenario::create();
        $user = $scenario->participant;
        $user->update(['role_id' => $role]);
        $user = $user->fresh();

        $this->assertSame($role, $user->role_id);
        $this->assertSame($admin, $user->isAdmin());
        $this->assertSame($manager, $user->isManager());
        $this->assertSame($helper, $user->isHelper());
    }

    public function test_user_without_role_has_no_permissions(): void
    {
        $scenario = CampScenario::create();
        $user = $scenario->participant;
        $user->role_id = null;

        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isManager());
        $this->assertFalse($user->isHelper());
    }

    public function test_membership_casts_role(): void
    {
        $scenario = CampScenario::create();

        $membership = CampUser::where('camp_id', $scenario->camp->id)->where('user_id', $scenario->helper->id)->firstOrFail();
        $this->assertSame(UserRole::Helper, $membership->role_id);
    }

    public function test_user_form_preselects_role_in_camp(): void
    {
        $scenario = CampScenario::create();

        $html = $this->actingAs($scenario->manager)
            ->get(route('dashboard.users.edit', $scenario->helper))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.UserRole::Helper->value.'"\s+selected="selected"/', $html);
    }
}
