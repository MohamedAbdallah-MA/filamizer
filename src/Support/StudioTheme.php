<?php

namespace DealArt\FilamentStudio\Support;

use DealArt\FilamentStudio\Settings\StudioSettings;
use Filament\Enums\ThemeMode;
use Illuminate\Support\Facades\Storage;

/**
 * Single place that turns stored settings into safe, validated theme values.
 * Every getter falls back to the config defaults when a value is missing or
 * invalid, and never throws (so artisan works before the settings exist).
 */
class StudioTheme
{
    public const HEX_PATTERN = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    public const SEMANTIC_COLORS = ['primary', 'success', 'warning', 'danger', 'info'];

    public const GRAY_SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    public static function value(string $key): mixed
    {
        $stored = rescue(fn () => app(StudioSettings::class)->{$key}, null, false);

        return $stored ?? static::default($key);
    }

    public static function default(string $key): mixed
    {
        return config("filament-studio.defaults.{$key}");
    }

    public static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match(self::HEX_PATTERN, $value) === 1;
    }

    public static function color(string $key): string
    {
        $value = static::value($key);

        return static::isHex($value) ? $value : static::default($key);
    }

    /**
     * @return array<int, string>
     */
    public static function grayPalette(): array
    {
        $stored = static::value('gray_palette');
        $defaults = static::default('gray_palette');

        return collect(self::GRAY_SHADES)
            ->mapWithKeys(fn (int $shade) => [
                $shade => static::isHex($stored[$shade] ?? null) ? $stored[$shade] : $defaults[$shade],
            ])
            ->all();
    }

    /**
     * @return array{background: string, surface: string, input: string, border: string}
     */
    public static function surfaces(bool $dark = false): array
    {
        $prefix = $dark ? 'dark_' : '';

        return [
            'background' => static::color("{$prefix}background"),
            'surface' => static::color("{$prefix}surface"),
            'input' => static::color("{$prefix}input"),
            'border' => static::color("{$prefix}border"),
        ];
    }

    public static function arabicFont(): string
    {
        return static::option('font_arabic', 'fonts.arabic');
    }

    public static function latinFont(): string
    {
        return static::option('font_latin', 'fonts.latin');
    }

    public static function isRtlLocale(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), ['ar', 'fa', 'ur', 'he'], true);
    }

    public static function fontFor(?string $locale = null): string
    {
        return static::isRtlLocale($locale) ? static::arabicFont() : static::latinFont();
    }

    /**
     * The font that is not the panel's primary font for this locale.
     */
    public static function secondaryFontFor(?string $locale = null): string
    {
        return static::isRtlLocale($locale) ? static::latinFont() : static::arabicFont();
    }

    public static function fontStack(?string $locale = null): string
    {
        return collect([static::fontFor($locale), static::secondaryFontFor($locale)])
            ->unique()
            ->map(fn (string $font) => "'{$font}'")
            ->push('ui-sans-serif', 'system-ui', 'sans-serif')
            ->implode(', ');
    }

    public static function rootFontSize(): string
    {
        return config('filament-studio.font_sizes.'.static::option('font_size', 'font_sizes').'.size');
    }

    public static function spacing(): string
    {
        return config('filament-studio.densities.'.static::option('density', 'densities').'.spacing');
    }

    /**
     * @return array{sm: string, md: string, lg: string, xl: string}
     */
    public static function radii(): array
    {
        $radius = config('filament-studio.radii.'.static::option('radius', 'radii'));

        return collect($radius)->only(['sm', 'md', 'lg', 'xl'])->all();
    }

    public static function darkMode(): string
    {
        return static::option('dark_mode', 'dark_modes');
    }

    public static function hasDarkMode(): bool
    {
        return static::darkMode() !== 'light';
    }

    public static function isDarkModeForced(): bool
    {
        return static::darkMode() === 'dark';
    }

    public static function defaultThemeMode(): ThemeMode
    {
        return match (static::option('default_theme_mode', 'default_theme_modes')) {
            'light' => ThemeMode::Light,
            'dark' => ThemeMode::Dark,
            default => ThemeMode::System,
        };
    }

    public static function skin(): string
    {
        return static::option('skin', 'skins');
    }

    public static function sidebarStyle(): string
    {
        return static::option('sidebar_style', 'sidebar_styles');
    }

    public static function isSidebarCollapsible(): bool
    {
        return static::sidebarStyle() === 'collapsible';
    }

    public static function backdrop(): string
    {
        $backdrop = static::option('backdrop', 'backdrops');

        // An image backdrop without an image falls back to the plain color.
        return $backdrop === 'image' && ! static::backdropImageUrl() ? 'none' : $backdrop;
    }

    public static function backdropImageUrl(): ?string
    {
        $path = static::value('backdrop_image');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return rescue(fn () => Storage::disk(config('filament-studio.backdrop_disk'))->url($path), null, false);
    }

    /**
     * A quoted CSS url() that cannot break out of the string or the <style> tag.
     */
    public static function cssUrl(string $url): string
    {
        $safe = str_replace(['\\', '"', "\n", "\r", '<', '>'], ['%5C', '%22', '', '', '%3C', '%3E'], $url);

        return "url(\"{$safe}\")";
    }

    /**
     * A stored integer clamped to its configured range.
     */
    public static function range(string $key): int
    {
        [$min, $max] = config("filament-studio.ranges.{$key}");
        $value = static::value($key);

        return is_numeric($value) ? max($min, min($max, (int) $value)) : static::default($key);
    }

    public static function googleFontsUrl(string $family): string
    {
        return 'https://fonts.googleapis.com/css2?family='.str_replace(' ', '+', $family).':wght@300;400;500;600;700&display=swap';
    }

    /**
     * A stored value that must be one of the keys of a config list.
     */
    protected static function option(string $key, string $configList): string
    {
        $value = static::value($key);
        $allowed = array_keys(config("filament-studio.{$configList}", []));

        return in_array($value, $allowed, true) ? $value : static::default($key);
    }
}
