<?php

namespace JeffersonGoncalves\LgpdRipd\Tests;

use JeffersonGoncalves\Lgpd\LgpdServiceProvider;
use JeffersonGoncalves\LgpdRecords\LgpdRecordsServiceProvider;
use JeffersonGoncalves\LgpdRipd\LgpdRipdServiceProvider;
use JeffersonGoncalves\LgpdRipd\Tests\Fixtures\Customer;
use JeffersonGoncalves\LgpdRipd\Tests\Fixtures\Patient;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            LgpdServiceProvider::class,
            LgpdRecordsServiceProvider::class,
            LgpdRipdServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('lgpd.models', [Customer::class, Patient::class]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(LgpdRecordsServiceProvider::class);
        $this->loadPackageMigrations(LgpdRipdServiceProvider::class);
    }
}
