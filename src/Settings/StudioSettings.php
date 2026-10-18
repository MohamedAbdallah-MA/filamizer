<?php

namespace DealArt\FilamentStudio\Settings;

use Spatie\LaravelSettings\Settings;

class StudioSettings extends Settings
{
    public ?string $preset = null;

    // Semantic colors
    public string $primary;

    public string $success;

    public string $warning;

    public string $danger;

    public string $info;

    // Neutral scale (50–950)
    public array $gray_palette = [];

    // Light surfaces
    public string $background;

    public string $surface;

    public string $input;

    public string $border;

    // Dark surfaces
    public string $dark_background;

    public string $dark_surface;

    public string $dark_input;

    public string $dark_border;

    // Typography
    public string $font_arabic;

    public string $font_latin;

    public string $font_size;

    // Layout
    public string $density;

    public string $radius;

    // Dark mode
    public string $dark_mode;

    public string $default_theme_mode;

    public static function group(): string
    {
        return 'studio';
    }

    /**
     * The fields edited by the appearance form, in storage order.
     *
     * @return array<int, string>
     */
    public static function fields(): array
    {
        return array_keys(config('filament-studio.defaults', []));
    }
}
