<?php

namespace Bastiaigner\LaravelXentral;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bastiaigner\LaravelXentral\Commands\LaravelXentralCommand;

class LaravelXentralServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-xentral')
            ->hasConfigFile();
    }
}
