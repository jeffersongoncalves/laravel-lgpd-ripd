<?php

namespace JeffersonGoncalves\LgpdRipd;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LgpdRipdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-lgpd-ripd')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
