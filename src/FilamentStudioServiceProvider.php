<?php

namespace DealArt\FilamentStudio;

use DealArt\FilamentStudio\Commands\InstallCommand;
use DealArt\FilamentStudio\Settings\StudioSettings;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentStudioServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        config()->push('settings.settings', StudioSettings::class);
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/settings');
    }
}
