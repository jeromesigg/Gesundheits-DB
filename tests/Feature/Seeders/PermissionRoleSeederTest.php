<?php

namespace Tests\Feature\Seeders;

use App\Models\Camp;
use App\Models\CampUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermissionRoleSeederTest extends TestCase
{
    /**
     * Das Admin-Passwort muss aus der Config kommen: bei gecachter Config liefert env() null.
     */
    public function test_admin_password_is_read_from_config(): void
    {
        CampUser::query()->delete();
        User::query()->delete();
        Camp::query()->delete();
        Role::query()->delete();
        config(['app.admin_password' => 'aus-der-config']);

        $this->seed(PermissionRoleSeeder::class);

        $this->assertTrue(Hash::check('aus-der-config', User::findOrFail(1)->password));
    }
}
