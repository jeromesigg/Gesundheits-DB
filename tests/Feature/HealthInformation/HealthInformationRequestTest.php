<?php

namespace Tests\Feature\HealthInformation;

use Tests\Support\CampScenario;
use Tests\TestCase;

class HealthInformationRequestTest extends TestCase
{
    public function test_search_without_code_redirects_back_with_error(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->from('/dashboard')
            ->get('/healthinformation/search')
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors();
    }

    public function test_search_with_code_of_own_camp_opens_health_information(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->get('/healthinformation/search?code='.$scenario->openCode)
            ->assertRedirect(route('healthinformation.show', $scenario->openInfo));
    }

    public function test_search_with_code_of_other_camp_finds_nothing(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->helper)
            ->from('/dashboard')
            ->get('/healthinformation/search?code='.$scenario->otherCode)
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors();
    }

    public function test_download_protocol_without_file_returns_not_found(): void
    {
        $scenario = CampScenario::create();

        $this->actingAs($scenario->manager)
            ->get('/healthinformation/downloadProtocol/'.$scenario->openInfo->code)
            ->assertNotFound();
    }
}
