<?php

namespace JeffersonGoncalves\LgpdRipd;

use JeffersonGoncalves\LgpdRipd\Commands\RipdCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LgpdRipdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lgpd-ripd')
            ->hasConfigFile()
            ->hasMigrations(['create_lgpd_impact_assessments_table'])
            ->hasCommand(RipdCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RipdManager::class);
    }
}
