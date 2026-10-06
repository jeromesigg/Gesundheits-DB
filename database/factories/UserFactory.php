<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = User::class;

    public function definition()
    {
        $username = $this->faker->unique()->userName();

        return [
            'username' => $username,
            'slug' => Str::slug($username),
            'email' => $this->faker->email,
            'password' => Hash::make('password'),
            'role_id' => config('status.role_Teilnehmer'),
            'email_verified_at' => now(),
            'camp_id' => 1,
            'demo' => true,
        ];
    }
}
