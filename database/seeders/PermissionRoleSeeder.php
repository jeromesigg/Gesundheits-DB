<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        // Insert some stuff
        Role::create(['id' => config('status.role_Administrator'), 'name' => 'Administrator', 'is_admin' => true, 'is_manager' => false, 'is_helper' => false]);
        Role::create(['id' => config('status.role_Lagerleiter'),    'name' => 'Lagerleitende',  'is_admin' => false, 'is_manager' => true, 'is_helper' => false]);
        Role::create(['id' => config('status.role_Helfer'),        'name' => 'Helfer',        'is_admin' => false, 'is_manager' => false, 'is_helper' => true]);
        Role::create(['id' => config('status.role_Teilnehmer'),    'name' => 'Teilnehmende',  'is_admin' => false, 'is_manager' => false, 'is_helper' => false]);

        $user = User::create([
            'id' => 1,
            'username' => 'Administrator',
            'email' => 'admin@ehealth',
            'slug' => 'administrator',
            'password' => Hash::make(config('app.admin_password')),
            'role_id' => config('status.role_Administrator'),
            'email_verified_at' => now(),
        ]);
        $camp = Camp::create(['name' => 'Global-Camp', 'global_camp' => true, 'code' => '0000']);
        $user->update(['camp_id' => $camp['id']]);
    }
}
