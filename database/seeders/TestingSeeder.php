<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    /**
     * Stammdaten für Tests: alles, was jede Instanz braucht, aber keine Demo-Daten.
     */
    public function run(): void
    {
        $this->call([
            BasisdatenSeeder::class,
            PermissionRoleSeeder::class,
            CitySeeder::class,
            GroupSeeder::class,
        ]);
    }
}
