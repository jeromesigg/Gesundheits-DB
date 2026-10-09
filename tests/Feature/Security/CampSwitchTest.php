<?php

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampUser;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Lager wechseln: nur in Lager mit aktiver Mitgliedschaft und ins Global-Camp. Die Rolle kommt aus der Mitgliedschaft.
 */
class CampSwitchTest extends TestCase
{
    public function test_member_can_switch_to_own_camp_with_role_of_membership(): void
    {
        $scenario = CampScenario::create();
        CampUser::create(['camp_id' => $scenario->otherCamp->id, 'user_id' => $scenario->manager->id, 'role_id' => UserRole::Helper, 'active' => true]);

        $this->actingAs($scenario->manager)
            ->patch(route('camps.update', $scenario->otherCamp))
            ->assertRedirect('/dashboard');

        $manager = $scenario->manager->fresh();
        $this->assertSame($scenario->otherCamp->id, $manager->camp_id);
        $this->assertSame(UserRole::Helper, $manager->role_id);
    }

    public function test_user_cannot_switch_to_camp_without_membership(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->otherManager)
            ->patch(route('camps.update', $scenario->camp))
            ->assertForbidden();

        $this->assertSame($scenario->otherCamp->id, $scenario->otherManager->fresh()->camp_id);
        $this->assertFalse(CampUser::where('camp_id', $scenario->camp->id)->where('user_id', $scenario->otherManager->id)->exists());
    }

    public function test_user_cannot_switch_to_camp_with_inactive_membership(): void
    {
        $scenario = CampScenario::create();
        CampUser::create(['camp_id' => $scenario->camp->id, 'user_id' => $scenario->otherManager->id, 'role_id' => UserRole::Helper, 'active' => false]);

        $this->actingAs($scenario->otherManager)
            ->patch(route('camps.update', $scenario->camp))
            ->assertForbidden();

        $this->assertSame($scenario->otherCamp->id, $scenario->otherManager->fresh()->camp_id);
    }

    public function test_user_can_switch_to_global_camp(): void
    {
        $scenario = CampScenario::create();
        $globalCamp = Camp::where('global_camp', true)->firstOrFail();

        $this->actingAs($scenario->helper)
            ->patch(route('camps.update', $globalCamp))
            ->assertRedirect('/dashboard');

        $helper = $scenario->helper->fresh();
        $this->assertSame($globalCamp->id, $helper->camp_id);
        $this->assertSame(UserRole::Participant, $helper->role_id);
    }
}
