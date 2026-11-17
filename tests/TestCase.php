<?php

namespace JeffersonGoncalves\LgpdRipd\Tests;

use JeffersonGoncalves\LgpdRipd\LgpdRipdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LgpdRipdServiceProvider::class,
        ];
    }
}
