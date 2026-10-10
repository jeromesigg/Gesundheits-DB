<?php

namespace Tests\Unit\Enums;

use App\Enums\TrafficLight;
use PHPUnit\Framework\TestCase;

/**
 * Ampel ohne Datenbank. Abgleich mit der Tabelle health_statuses: Tests\Feature\Enums\TrafficLightTest.
 */
class TrafficLightTest extends TestCase
{
    public function test_each_light_has_its_css_class(): void
    {
        $this->assertSame(
            ['green', 'yellow', 'red'],
            array_map(fn (TrafficLight $light) => $light->cssClass(), TrafficLight::cases())
        );
    }

    public function test_values_are_ordered_by_urgency(): void
    {
        $this->assertLessThan(TrafficLight::Yellow->value, TrafficLight::Green->value);
        $this->assertLessThan(TrafficLight::Red->value, TrafficLight::Yellow->value);
    }
}
