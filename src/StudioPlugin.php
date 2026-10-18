<?php

namespace DealArt\FilamentStudio;

use Closure;
use DealArt\FilamentStudio\Support\StudioTheme;
use Filament\Contracts\Plugin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

class StudioPlugin implements Plugin
{
    protected ?Closure $canCustomize = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): ?static
    {
        $panel = filament()->getCurrentOrDefaultPanel();

        return $panel?->hasPlugin('filament-studio') ? $panel->getPlugin('filament-studio') : null;
    }

    public function getId(): string
    {
        return 'filament-studio';
    }

    /**
     * Decide who can see and edit the appearance settings.
     */
    public function canCustomize(?Closure $callback): static
    {
        $this->canCustomize = $callback;

        return $this;
    }

    public function userCanCustomize(): bool
    {
        return $this->canCustomize ? (bool) app()->call($this->canCustomize) : true;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->colors(fn (): array => [
                ...collect(StudioTheme::SEMANTIC_COLORS)
                    ->mapWithKeys(fn (string $name) => [$name => StudioTheme::color($name)])
                    ->all(),
                'gray' => StudioTheme::grayPalette(),
            ])
            ->font(
                fn (): string => StudioTheme::fontFor(),
                provider: GoogleFontProvider::class,
            )
            ->darkMode(
                fn (): bool => StudioTheme::hasDarkMode(),
                isForced: fn (): bool => StudioTheme::isDarkModeForced(),
            )
            ->defaultThemeMode(StudioTheme::defaultThemeMode())
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament-studio::styles'),
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
