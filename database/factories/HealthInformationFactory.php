<?php

namespace Database\Factories;

use App\Enums\TrafficLight;
use App\Helper\Helper;
use App\Models\HealthInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Medizinischer Teil eines Gesundheitsblatts. Verknüpft mit dem HealthForm nur über den
 * (hier unverschlüsselten) persönlichen Code.
 *
 * @extends Factory<HealthInformation>
 */
class HealthInformationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Helper::generateUniqueCode(),
            'allergy' => $this->faker->randomElement([null, 'Nüsse', 'Bienenstiche']),
            'drug_longterm' => null,
            'health_status_id' => TrafficLight::Green,
        ];
    }
}
