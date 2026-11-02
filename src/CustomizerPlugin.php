<?php

namespace FilamentCustomizer;

use Closure;
use FilamentCustomizer\Filament\Pages\ManageAppearance;
use FilamentCustomizer\Filament\Pages\MyAppearance;
use FilamentCustomizer\Support\StudioTheme;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

class CustomizerPlugin implements Plugin
{
    protected ?Closure $canCustomize = null;

    protected bool $hasSettingsPage = true;

    protected bool $hasPersonalAppearance = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): ?static
    {
        $panel = filament()->getCurrentOrDefaultPanel();

        return $panel?->hasPlugin('filament-customizer') ? $panel->getPlugin('filament-customizer') : null;
    }

    public function getId(): string
    {
        return 'filament-customizer';
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
        return $this->canCustomize ? (bool) app()->call($this->canCustomize) : false;
    }

    public function settingsPage(bool $condition = true): static
    {
        $this->hasSettingsPage = $condition;

        return $this;
    }

    public function hasSettingsPage(): bool
    {
        return $this->hasSettingsPage;
    }

    public function personalAppearance(bool $condition = true): static
    {
        $this->hasPersonalAppearance = $condition;

        return $this;
    }

    public function hasPersonalAppearance(): bool
    {
        return $this->hasPersonalAppearance;
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
            ->sidebarCollapsibleOnDesktop(fn (): bool => StudioTheme::isSidebarCollapsible())
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament-customizer::styles'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament-customizer::liquid-glass'),
            )
            ->pages([
                ...($this->hasSettingsPage() ? [ManageAppearance::class] : []),
                ...($this->hasPersonalAppearance() ? [MyAppearance::class] : []),
            ])
            ->userMenuItems($this->hasPersonalAppearance() ? [
                'studio-appearance' => Action::make('studio-appearance')
                    ->label(fn (): string => __('My appearance'))
                    ->icon('heroicon-o-swatch')
                    ->url(fn (): string => MyAppearance::getUrl())
                    ->visible(fn (): bool => MyAppearance::canAccess()),
            ] : []);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
