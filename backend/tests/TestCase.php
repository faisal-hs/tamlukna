<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests using RefreshDatabase seed roles and permissions once per run,
     * exactly as production deployments do.
     */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;

    /**
     * Refuse to boot against anything other than a *_test database,
     * so RefreshDatabase can never wipe development data.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Refusing to run tests against non-test database [{$database}] on connection [{$connection}]."
            );
        }

        return $app;
    }
}
