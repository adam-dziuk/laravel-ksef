<?php

namespace AdamDziuk\LaravelKsef;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelKsefServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ksef')
            ->hasConfigFile()
            ->hasMigration('create_ksef_auth_sessions_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Ksef::class);
    }
}
