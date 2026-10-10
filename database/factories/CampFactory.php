<?php

namespace Database\Factories;

use App\Models\Camp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camp>
 */
class CampFactory extends Factory
{
    /**
     * Ein laufendes Lager mit eigenem Lager-Code (4-stellig, wie Helper::generateUniqueCampCode()).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Lager '.$this->faker->unique()->city(),
            'code' => $this->faker->unique()->numberBetween(1000, 9999),
            'finish' => false,
            'counter' => 0,
            'independent_form_fill' => false,
            'forms_finished' => false,
            'closed_when_finished' => false,
            'show_names' => true,
            'konekta' => false,
        ];
    }

    /**
     * Abgeschlossenes Lager: Teilnehmende können ihr Gesundheitsblatt nicht mehr öffnen.
     */
    public function finished(): static
    {
        return $this->state(fn () => ['finish' => true]);
    }
}
