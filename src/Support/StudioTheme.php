<?php

namespace DealArt\FilamentStudio\Support;

use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
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
        return StudioPreferences::make()->value($key) ?? static::default($key);
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
    public static function grayPalette(bool $dark = false): array
    {
        $stored = static::value($dark ? 'dark_gray_palette' : 'gray_palette');
        $defaults = static::default($dark ? 'dark_gray_palette' : 'gray_palette');

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

    public static function skin(bool $dark = false): string
    {
        return static::option($dark ? 'dark_skin' : 'skin', 'skins');
    }

    public static function sidebarStyle(): string
    {
        return static::option('sidebar_style', 'sidebar_styles');
    }

    public static function isSidebarCollapsible(): bool
    {
        return static::sidebarStyle() === 'collapsible';
    }

    public static function backdrop(bool $dark = false): string
    {
        $backdrop = static::option($dark ? 'dark_backdrop' : 'backdrop', 'backdrops');

        // An image backdrop without an image falls back to the plain color.
        return $backdrop === 'image' && ! static::backdropImageUrl($dark) ? 'none' : $backdrop;
    }

    public static function backdropImageUrl(bool $dark = false): ?string
    {
        $path = static::value($dark ? 'dark_backdrop_image' : 'backdrop_image');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return rescue(fn () => Storage::disk(config('filament-studio.backdrop_disk'))->url($path), null, false);
    }

    public static function isAuroraAnimated(bool $dark = false): bool
    {
        return filter_var(static::value($dark ? 'dark_aurora_animated' : 'aurora_animated'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? (bool) static::default('aurora_animated');
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

    /**
     * Drop or clamp invalid values before saving. Hidden form fields are saved
     * but not validated, so this is the last line of defense on write.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function sanitizeValues(array $values): array
    {
        $options = [
            'skin' => 'skins', 'dark_skin' => 'skins', 'dark_backdrop' => 'backdrops', 'dark_preset' => 'themes', 'sidebar_style' => 'sidebar_styles', 'backdrop' => 'backdrops',
            'font_size' => 'font_sizes', 'density' => 'densities', 'radius' => 'radii',
            'dark_mode' => 'dark_modes', 'default_theme_mode' => 'default_theme_modes',
            'font_arabic' => 'fonts.arabic', 'font_latin' => 'fonts.latin', 'preset' => 'themes',
        ];

        $clean = [];

        foreach ($values as $key => $value) {
            $default = config("filament-studio.defaults.{$key}");

            if (isset($options[$key])) {
                if ($value === null && in_array($key, ['preset', 'dark_preset'], true)) {
                    $clean[$key] = null;
                } elseif (in_array($value, array_keys(config("filament-studio.{$options[$key]}", [])), true)) {
                    $clean[$key] = $value;
                }
            } elseif (config()->has("filament-studio.ranges.{$key}")) {
                [$min, $max] = config("filament-studio.ranges.{$key}");

                if (is_numeric($value)) {
                    $clean[$key] = max($min, min($max, (int) $value));
                }
            } elseif (in_array($key, ['gray_palette', 'dark_gray_palette'], true)) {
                if (is_array($value)) {
                    $clean[$key] = collect(self::GRAY_SHADES)
                        ->mapWithKeys(fn (int $shade) => [$shade => static::isHex($value[$shade] ?? null) ? $value[$shade] : $default[$shade]])
                        ->all();
                }
            } elseif ($key === 'user_skins') {
                if (is_array($value)) {
                    $clean[$key] = array_values(array_intersect($value, array_keys(config('filament-studio.skins', []))));
                }
            } elseif (in_array($key, ['aurora_animated', 'dark_aurora_animated', 'glass_auto_readability', 'dark_glass_auto_readability'], true)) {
                $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($boolean !== null) {
                    $clean[$key] = $boolean;
                }
            } elseif (in_array($key, ['backdrop_image', 'dark_backdrop_image'], true) || $key === 'navigation_layout') {
                $clean[$key] = $value;
            } elseif (is_string($default) && str_starts_with($default, '#')) {
                if (static::isHex($value)) {
                    $clean[$key] = $value;
                }
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * The primary color scale (50–950) for the dark theme, in Filament's format.
     *
     * @return array<int, string>
     */
    public static function darkPrimaryPalette(): array
    {
        return static::darkColorPalette('primary');
    }

    public static function darkColorPalette(string $color): array
    {
        return rescue(fn () => Color::generatePalette(static::color('dark_'.$color)), [], false);
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
