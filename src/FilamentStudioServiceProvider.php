<?php

namespace DealArt\FilamentStudio;

use DealArt\FilamentStudio\Commands\InstallCommand;
use DealArt\FilamentStudio\Navigation\StudioNavigationManager;
use DealArt\FilamentStudio\Settings\StudioSettings;
use Filament\Navigation\NavigationManager;
use DealArt\FilamentStudio\Support\StudioCss;
use DealArt\FilamentStudio\Support\StudioPreferences;
use Filament\Support\Facades\FilamentAsset;
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

        $this->app->scoped(StudioPreferences::class);
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom([__DIR__.'/../database/settings', __DIR__.'/../database/migrations']);

        // Filament binds its manager in register(); rebinding here (boot) wins.
        $this->app->scoped(NavigationManager::class, fn (): NavigationManager => new StudioNavigationManager);

        FilamentAsset::register([
            StudioCss::make('filament-studio', __DIR__.'/../resources/dist/filament-studio.css'),
        ], package: 'deal-art/filament-studio');
    }
}
