<?php

namespace Weble\ReleaseCommand;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Weble\ReleaseCommand\Commands\Release;

class ReleaseCommandServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('release-command')
            ->hasConfigFile()
            ->hasCommand(Release::class);
    }
}
