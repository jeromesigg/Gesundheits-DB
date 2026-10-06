<?php

namespace Tests\Feature;

use App\Models\Camp;
use App\Models\City;
use App\Models\Group;
use App\Models\HealthStatus;
use App\Models\Role;
use App\Models\User;
use Tests\Support\CampScenario;
use Tests\TestCase;

class TestInfrastructureTest extends TestCase
{
    public function test_both_connections_use_sqlite_in_memory(): void
    {
        foreach ([config('database.default'), 'mysql_info'] as $connection) {
            $this->assertSame('sqlite', config("database.connections.$connection.driver"));
            $this->assertSame(':memory:', config("database.connections.$connection.database"));
        }
    }

    public function test_testing_seeder_creates_master_data_without_demo_data(): void
    {
        $this->assertSame(4, Role::count());
        $this->assertSame(3, HealthStatus::count());
        $this->assertTrue(User::findOrFail(1)->isAdmin());
        $this->assertTrue(Camp::where('global_camp', true)->exists());
        $this->assertGreaterThan(0, Group::count());
        $this->assertGreaterThan(0, City::count());
        $this->assertFalse(Camp::where('demo', true)->exists());
    }

    public function test_mysql_info_connection_is_reset_between_tests_first(): void
    {
        CampScenario::create();

        $this->assertGreaterThan(0, Group::count());
    }

    public function test_mysql_info_connection_is_reset_between_tests_second(): void
    {
        $this->assertSame(2, Group::count());
    }

    public function test_scenario_links_health_form_and_health_information_by_code(): void
    {
        $scenario = CampScenario::create();

        $this->assertSame((string) $scenario->openCode, (string) $scenario->openForm->code);
        $this->assertSame($scenario->openCode, (int) $scenario->openInfo->code);
        $this->assertSame($scenario->camp->id, $scenario->openForm->camp_id);
        $this->assertCount(1, $scenario->openInfo->questions);
        $this->assertCount(2, $scenario->openInfo->interventions);
    }

    public function test_scenario_users_have_expected_roles(): void
    {
        $scenario = CampScenario::create();

        $this->assertTrue($scenario->admin->isAdmin());
        $this->assertTrue($scenario->manager->isManager());
        $this->assertFalse($scenario->manager->isAdmin());
        $this->assertTrue($scenario->helper->isHelper());
        $this->assertFalse($scenario->helper->isManager());
        $this->assertFalse($scenario->participant->isHelper());
        $this->assertSame($scenario->camp->id, $scenario->helper->camp_id);
    }

    public function test_dashboard_renders_for_manager(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)->get('/dashboard')->assertOk();
    }
}
