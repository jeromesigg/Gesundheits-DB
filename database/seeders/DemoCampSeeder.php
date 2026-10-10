<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\Intervention;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class DemoCampSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $user = User::updateOrCreate([
            'username' => 'lagerleiter@demo'], [
                'email' => 'lagerleiter@demo',
                'slug' => 'lagerleiter@demo',
                'password' => Hash::make('lagerleiter@demo'),
                'email_verified_at' => now(),
                'role_id' => UserRole::CampLeader,
                'camp_id' => 1,
                'demo' => true]);
        $camp = Camp::updateOrCreate([
            'name' => 'Demo-Kurs'], [
                'demo' => true,
                'code' => 1234,
                'user_id' => $user['id'],
                'finish' => false,
                'counter' => 0, ]);
        $user->update(['camp_id' => $camp['id']]);
        CampUser::updateOrCreate([
            'camp_id' => $camp['id'],
            'user_id' => $user['id']], [
                'role_id' => UserRole::CampLeader,
            ]);
        CampUser::updateOrCreate([
            'camp_id' => 1,
            'user_id' => $user['id']], [
                'role_id' => UserRole::Participant,
            ]);
        $user_leiter1 = User::updateOrCreate([
            'username' => 'leiter1@demo'], [
                'email' => 'leiter1@demo',
                'slug' => 'leiter1@demo',
                'password' => Hash::make('leiter1@demo'),
                'email_verified_at' => now(),
                'role_id' => UserRole::Helper,
                'camp_id' => $camp['id'],
                'demo' => true]);
        CampUser::updateOrCreate([
            'camp_id' => $camp['id'],
            'user_id' => $user_leiter1['id']], [
                'role_id' => UserRole::Helper,
            ]);
        CampUser::updateOrCreate([
            'camp_id' => 1,
            'user_id' => $user_leiter1['id']], [
                'role_id' => UserRole::Participant,
            ]);
        $user_leiter2 = User::updateOrCreate([
            'username' => 'leiter2@demo'], [
                'email' => 'leiter2@demo',
                'slug' => 'leiter2@demo',
                'password' => Hash::make('leiter2@demo'),
                'email_verified_at' => now(),
                'role_id' => UserRole::Helper,
                'camp_id' => $camp['id'],
                'demo' => true]);
        CampUser::updateOrCreate([
            'camp_id' => $camp['id'],
            'user_id' => $user_leiter2['id']], [
                'role_id' => UserRole::Helper,
            ]);
        CampUser::updateOrCreate([
            'camp_id' => 1,
            'user_id' => $user_leiter2['id']], [
                'role_id' => UserRole::Participant,
            ]);
        if (count($camp->health_infos) === 0) {
            for ($i = 0; $i < 20; $i++) {
                $health_form = HealthForm::factory()->create(['camp_id' => $camp['id'], 'code' => Crypt::encryptString($i + 100000)]);
                $health_info = HealthInformation::firstOrCreate(['code' => $health_form['code']], ['camp_id' => $camp['id']]);
                for ($j = 0; $j < 10; $j++) {
                    Intervention::factory()
                        ->for($health_info, 'health_information')->create();
                }
            }
        }
    }
}
