<?php

namespace Tests\Feature\Enums;

use App\Enums\TrafficLight;
use App\Helper\Helper;
use App\Models\HealthStatus;
use App\Models\Intervention;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Ampel (Dringlichkeit) von Interventionen und Gesundheitsinfos.
 */
class TrafficLightTest extends TestCase
{
    public function test_enum_values_match_health_status_table(): void
    {
        $this->assertSame(
            HealthStatus::orderBy('id')->pluck('id')->all(),
            array_map(fn (TrafficLight $light) => $light->value, TrafficLight::cases())
        );
    }

    /**
     * @return array<string, array{?TrafficLight, string}>
     */
    public static function lights(): array
    {
        return [
            'grün' => [TrafficLight::Green, 'circle green'],
            'gelb' => [TrafficLight::Yellow, 'circle yellow'],
            'rot' => [TrafficLight::Red, 'circle red'],
            'ohne Status gilt als grün' => [null, 'circle green'],
        ];
    }

    #[DataProvider('lights')]
    public function test_open_intervention_shows_its_light(?TrafficLight $light, string $expectedCircle): void
    {
        $scenario = CampScenario::create();
        $intervention = $scenario->openIntervention;
        $intervention->update(['health_status_id' => $light]);

        $html = Helper::getHealthStatus($intervention->fresh());

        $this->assertStringContainsString('"'.$expectedCircle.'"', $html);
        $this->assertSame(1, substr_count($html, 'green') + substr_count($html, 'yellow') + substr_count($html, 'red'));
    }

    public function test_closed_intervention_shows_no_light(): void
    {
        $scenario = CampScenario::create();

        $this->assertNull(Helper::getHealthStatus($scenario->closedIntervention));
    }

    public function test_model_casts_status_to_enum(): void
    {
        $scenario = CampScenario::create();

        $this->assertSame(TrafficLight::Yellow, $scenario->openIntervention->fresh()->health_status_id);
        $this->assertSame(TrafficLight::Green, $scenario->openInfo->fresh()->health_status_id);
    }

    public function test_overview_shows_most_urgent_open_intervention(): void
    {
        $scenario = CampScenario::create();
        Intervention::factory()->create([
            'health_information_id' => $scenario->openInfo->id,
            'user_id' => $scenario->helper->id,
            'health_status_id' => TrafficLight::Red,
        ]);

        $row = collect($this->actingAs($scenario->helper)->getJson('/healthinformation/createDataTables')->json('data'))
            ->first(fn ($row) => str_contains($row['code'], (string) $scenario->openCode));

        $this->assertStringContainsString('circle red', $row['status']);
    }

    public function test_intervention_form_preselects_current_light(): void
    {
        $scenario = CampScenario::create();

        $html = $this->actingAs($scenario->helper)
            ->get(route('interventions.edit', $scenario->openIntervention))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.TrafficLight::Yellow->value.'"\s+selected="selected"/', $html);
    }
}
