<?php

namespace Filamizer;

use Filamizer\Commands\InstallCommand;
use Filamizer\Navigation\StudioNavigationManager;
use Filamizer\Settings\StudioSettings;
use Filament\Navigation\NavigationManager;
use Filamizer\Support\StudioCss;
use Filamizer\Support\StudioPreferences;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamizerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filamizer';

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
            StudioCss::make('filamizer', __DIR__.'/../resources/dist/filamizer.css'),
        ], package: 'mohamedabdallah-ma/filamizer');
    }
}
