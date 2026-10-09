<?php

namespace Tests;

use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Stammdaten (Rollen, Admin, Global-Camp, Gesundheitsstatus, Städte, Gruppen) ohne Demo-Daten.
     */
    protected $seeder = TestingSeeder::class;

    /**
     * Gesundheitsdaten liegen auf einer eigenen Verbindung (mysql_info), die in Tests
     * eine zweite SQLite-In-Memory-DB ist. Ohne diesen Eintrag ist sie ab dem zweiten Test leer.
     *
     * @var array<int, string>
     */
    protected array $connectionsToTransact = ['sqlite', 'mysql_info'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config(['excel.temporary_files.local_path' => sys_get_temp_dir().'/laravel-excel-tests']);
    }

    /**
     * RefreshDatabase leert die Datenbank. Bei gecachter Config (php artisan config:cache)
     * greifen die Werte aus phpunit.xml nicht und es würde die echte Datenbank treffen.
     */
    protected function beforeRefreshingDatabase()
    {
        foreach ([config('database.default'), 'mysql_info'] as $connection) {
            $driver = config("database.connections.$connection.driver");
            $database = config("database.connections.$connection.database");

            if ($driver !== 'sqlite' || $database !== ':memory:') {
                throw new RuntimeException(
                    "Tests würden gegen '$connection' ($driver, $database) laufen statt gegen SQLite in-memory. "
                    ."Config-Cache aktiv? 'php artisan config:clear' ausführen."
                );
            }
        }
    }
}
