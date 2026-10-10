<?php

namespace Tests\Feature\Camp;

use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\User;
use Tests\TestCase;

/**
 * Admins für neue Lager: alle Personen mit Admin-Rolle im Global-Camp werden Admin im neuen Lager
 * (früher fest User 1).
 */
class CampAdminsTest extends TestCase
{
    public function test_seeded_admin_is_admin_of_global_camp(): void
    {
        $this->assertSame(UserRole::Administrator, $this->membership($this->globalCamp(), User::findOrFail(1))?->role_id);
    }

    public function test_new_camp_gets_all_admins_of_global_camp(): void
    {
        $secondAdmin = User::factory()->admin()->inCamp($this->globalCamp())->create();
        $globalHelper = User::factory()->helper()->inCamp($this->globalCamp())->create();
        $leader = User::factory()->create();

        $this->actingAs($leader)->post(route('camps.store'), ['name' => 'Sommerlager']);

        $camp = Camp::where('name', 'Sommerlager')->firstOrFail();
        $this->assertSame(UserRole::Administrator, $this->membership($camp, User::findOrFail(1))?->role_id);
        $this->assertSame(UserRole::Administrator, $this->membership($camp, $secondAdmin)?->role_id);
        $this->assertNull($this->membership($camp, $globalHelper));
        $this->assertSame(UserRole::CampLeader, $this->membership($camp, $leader)?->role_id);
    }

    public function test_former_admin_no_longer_added(): void
    {
        $formerAdmin = User::factory()->admin()->inCamp($this->globalCamp())->create();
        $this->membership($this->globalCamp(), $formerAdmin)->update(['role_id' => UserRole::Participant]);

        $camp = Camp::factory()->create();

        $this->assertNull($this->membership($camp, $formerAdmin));
    }

    public function test_admins_are_not_listed_as_camp_members(): void
    {
        $camp = Camp::factory()->create();

        $this->assertFalse($camp->allUsers()->where('users.id', 1)->exists());
    }

    private function globalCamp(): Camp
    {
        return Camp::where('global_camp', true)->firstOrFail();
    }

    private function membership(Camp $camp, User $user): ?CampUser
    {
        return CampUser::where('camp_id', $camp->id)->where('user_id', $user->id)->first();
    }
}
