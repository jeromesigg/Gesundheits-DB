<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampUser>
 */
class CampUserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camp_id' => Camp::factory(),
            'user_id' => User::factory(),
            'role_id' => config('status.role_Helfer'),
            'active' => true,
        ];
    }
}
