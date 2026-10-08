<?php

namespace Tests\Feature\Security;

use App\Models\Camp;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Lager bearbeiten und löschen: nur als Lagerleitung dieses Lagers.
 */
class CampManagementTest extends TestCase
{
    public function test_manager_can_update_own_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->patch(route('dashboard.camps.update', $scenario->camp), $this->payload(['name' => 'Neuer Name']))
            ->assertRedirect('/dashboard/camps');

        $this->assertSame('Neuer Name', $scenario->camp->fresh()->name);
    }

    public function test_manager_cannot_edit_update_or_delete_other_camp(): void
    {
        $scenario = CampScenario::create();
        $this->actingAs($scenario->manager);

        $this->get(route('dashboard.camps.edit', $scenario->otherCamp))->assertForbidden();
        $this->patch(route('dashboard.camps.update', $scenario->otherCamp), $this->payload(['name' => 'Übernommen']))->assertForbidden();
        $this->delete(route('dashboard.camps.destroy', $scenario->otherCamp))->assertForbidden();

        $otherCamp = $scenario->otherCamp->fresh();
        $this->assertNotSame('Übernommen', $otherCamp->name);
        $this->assertFalse($otherCamp->finish);
    }

    public function test_manager_cannot_delete_camp_where_only_helper(): void
    {
        $scenario = CampScenario::create();
        $scenario->helper->update(['role_id' => config('status.role_Lagerleiter')]);

        $this->actingAs($scenario->helper->fresh())
            ->delete(route('dashboard.camps.destroy', $scenario->camp))
            ->assertForbidden();

        $this->assertFalse($scenario->camp->fresh()->finish);
    }

    public function test_update_ignores_fields_outside_the_form(): void
    {
        $scenario = CampScenario::create();
        $code = $scenario->camp->code;

        $this->actingAs($scenario->manager)
            ->patch(route('dashboard.camps.update', $scenario->camp), $this->payload([
                'global_camp' => 1,
                'code' => 1111,
                'finish' => 1,
                'counter' => 99,
            ]));

        $camp = $scenario->camp->fresh();
        $this->assertFalse($camp->global_camp);
        $this->assertSame($code, $camp->code);
        $this->assertFalse($camp->finish);
    }

    public function test_owner_must_be_manager_of_the_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->patch(route('dashboard.camps.update', $scenario->camp), $this->payload(['user_id' => $scenario->otherManager->id]))
            ->assertSessionHasErrors('user_id');

        $this->assertSame($scenario->manager->id, $scenario->camp->fresh()->user_id);
    }

    public function test_manager_can_delete_own_camp(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->delete(route('dashboard.camps.destroy', $scenario->camp))
            ->assertRedirect('/dashboard');

        $this->assertTrue($scenario->camp->fresh()->finish);
        $this->assertSame(Camp::where('global_camp', true)->value('id'), $scenario->helper->fresh()->camp_id);
        $this->assertSame($scenario->otherCamp->id, $scenario->otherManager->fresh()->camp_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lager',
            'end_date' => now()->addWeek()->toDateString(),
            'group_text' => '',
            'show_names' => 1,
        ], $overrides);
    }
}
