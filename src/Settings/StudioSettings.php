<?php

namespace Filamizer\Settings;

use Spatie\LaravelSettings\Settings;

class StudioSettings extends Settings
{
    public ?string $preset = null;

    // Semantic colors
    public string $primary;

    public string $text_color;

    public string $muted_text_color;

    public string $dark_text_color;

    public string $dark_muted_text_color;

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

    // Skins
    public string $skin;

    public string $sidebar_style;

    public string $backdrop;

    public ?string $backdrop_image = null;

    public int $glass_blur;

    public int $glass_opacity;

    public bool $glass_auto_readability = true;

    public bool $dark_glass_auto_readability = true;

    public int $glass_refraction;

    public int $backdrop_intensity;

    // Sidebar builder: ['groups' => [...], 'items' => [...]], empty = Filament's default sidebar
    public array $navigation_layout = [];

    // Per-theme values (the light theme uses the plain keys above)
    public string $dark_primary;

    public int $dark_glass_opacity;

    public int $dark_backdrop_intensity;

    public int $dark_glow_intensity;

    // Skins users may pick for themselves; empty turns "My appearance" off
    public array $user_skins = [];

    public int $glow_intensity;

    public string $backdrop_color_1;

    public string $backdrop_color_2;

    public string $backdrop_color_3;

    public string $dark_backdrop_color_1;

    public string $dark_backdrop_color_2;

    public string $dark_backdrop_color_3;

    public int $backdrop_angle;

    public int $dark_backdrop_angle;

    public int $aurora_speed;

    public int $dark_aurora_speed;

    public bool $aurora_animated;

    public string $dark_skin;

    public string $dark_backdrop;

    public ?string $dark_backdrop_image = null;

    public int $dark_glass_blur;

    public int $dark_glass_refraction;

    public bool $dark_aurora_animated;

    public ?string $dark_preset = null;

    public string $dark_success;

    public string $dark_warning;

    public string $dark_danger;

    public string $dark_info;

    public array $dark_gray_palette = [];

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
        return array_keys(config('filamizer.defaults', []));
    }
}
