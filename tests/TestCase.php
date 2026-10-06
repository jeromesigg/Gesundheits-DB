<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected bool $seed = true;

    /**
     * Gesundheitsdaten liegen auf einer eigenen Verbindung (mysql_info), die in Tests
     * eine zweite SQLite-In-Memory-DB ist. Ohne diesen Eintrag ist sie ab dem zweiten Test leer.
     *
     * @var array<int, string>
     */
    protected array $connectionsToTransact = ['sqlite', 'mysql_info'];
}
