<?php

namespace FilamentCustomizer;

use FilamentCustomizer\Commands\InstallCommand;
use FilamentCustomizer\Navigation\StudioNavigationManager;
use FilamentCustomizer\Settings\StudioSettings;
use Filament\Navigation\NavigationManager;
use FilamentCustomizer\Support\StudioCss;
use FilamentCustomizer\Support\StudioPreferences;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCustomizerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-customizer';

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
            StudioCss::make('filament-customizer', __DIR__.'/../resources/dist/filament-customizer.css'),
        ], package: 'filament-customizer/filament-customizer');
    }
}
