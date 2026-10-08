<?php

namespace Database\Seeders;

use App\Enums\TrafficLight;
use App\Models\HealthStatus;
use Illuminate\Database\Seeder;

class BasisdatenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        HealthStatus::firstOrCreate(['id' => TrafficLight::Green->value],
            [
                'name' => 'Grün',
            ]);
        HealthStatus::firstOrCreate(['id' => TrafficLight::Yellow->value],
            [
                'name' => 'Gelb',
            ]);
        HealthStatus::firstOrCreate(['id' => TrafficLight::Red->value],
            [
                'name' => 'Rot',
            ]);
    }
}
