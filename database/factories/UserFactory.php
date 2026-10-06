<?php

namespace Database\Factories;

use App\Models\Camp;
use App\Models\CampUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Verifizierter, echter (kein Demo-)User im Global-Camp (id 1).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = $this->faker->unique()->userName();

        return [
            'username' => $username,
            'slug' => Str::slug($username),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role_id' => config('status.role_Teilnehmer'),
            'email_verified_at' => now(),
            'camp_id' => 1,
            'demo' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role_id' => config('status.role_Administrator')]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role_id' => config('status.role_Lagerleiter')]);
    }

    public function helper(): static
    {
        return $this->state(fn () => ['role_id' => config('status.role_Helfer')]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function demo(): static
    {
        return $this->state(fn () => ['demo' => true]);
    }

    /**
     * Macht das Lager zum aktuellen Lager des Users und legt die Mitgliedschaft (camp_users) an,
     * mit derselben Rolle wie der User.
     */
    public function inCamp(Camp $camp): static
    {
        return $this->state(fn () => ['camp_id' => $camp->id])
            ->afterCreating(function (User $user) use ($camp) {
                CampUser::create([
                    'camp_id' => $camp->id,
                    'user_id' => $user->id,
                    'role_id' => $user->role_id,
                    'active' => true,
                ]);
            });
    }
}
