<?php

namespace Tests\Feature\Models;

use Tests\Support\CampScenario;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_role_checks_return_booleans(): void
    {
        $scenario = CampScenario::create();

        $this->assertTrue($scenario->admin->isAdmin());
        $this->assertTrue($scenario->manager->isManager());
        $this->assertFalse($scenario->manager->isAdmin());
        $this->assertTrue($scenario->helper->isHelper());
        $this->assertFalse($scenario->helper->isManager());
        $this->assertFalse($scenario->participant->isHelper());
    }
}
