<?php

namespace DealArt\FilamentStudio\Filament;

use DealArt\FilamentStudio\StudioPlugin;
use DealArt\FilamentStudio\Support\StudioTheme;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;

class StudioAppearanceTab
{
    public static function make(): Tab
    {
        return Tab::make(__('Dashboard Appearance & Design System'))
            ->icon('heroicon-o-swatch')
            ->statePath('studio')
            ->visible(fn (): bool => StudioPlugin::get()?->userCanCustomize() ?? false)
            ->schema([
                View::make('filament-studio::live-preview')
                    ->columnSpanFull(),

                static::presetsSection(),
                static::semanticColorsSection(),
                static::surfacesSection(),
                static::neutralPaletteSection(),
                static::typographySection(),
                static::layoutSection(),
                static::darkModeSection(),
            ]);
    }

    protected static function presetsSection(): Section
    {
        return Section::make(__('Theme Presets'))
            ->description(__('Select a complete visual identity preset to instantly configure the entire dashboard palette and surfaces.'))
            ->schema([
                Select::make('preset')
                    ->label(__('Select Theme Preset'))
                    ->placeholder(__('Choose a preset or customize below...'))
                    ->options(static::translatedOptions('themes'))
                    ->in(array_keys(config('filament-studio.themes', [])))
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $theme = config("filament-studio.themes.{$state}");

                        if (! $theme) {
                            return;
                        }

                        foreach ([...StudioTheme::SEMANTIC_COLORS, 'background', 'surface', 'input', 'border'] as $key) {
                            $set($key, $theme[$key]);
                        }

                        static::setGrayPalette($set, $theme['gray']);

                        // Dark surfaces follow the theme's own gray scale.
                        $set('dark_background', $theme['gray'][950]);
                        $set('dark_surface', $theme['gray'][900]);
                        $set('dark_input', $theme['gray'][900]);
                        $set('dark_border', $theme['gray'][800]);
                    })
                    ->columnSpanFull(),
            ]);
    }

    protected static function semanticColorsSection(): Section
    {
        return Section::make(__('Brand & Semantic Colors'))
            ->description(__('Configure the primary brand color and status indicators for Filament.'))
            ->columns(3)
            ->schema([
                static::colorPicker('primary', __('Primary Brand Color'), __('Main accent for buttons, active navigation, and links.')),
                static::colorPicker('success', __('Success Color'), __('Used for confirmations and positive status badges.')),
                static::colorPicker('warning', __('Warning Color'), __('Used for alerts and pending statuses.')),
                static::colorPicker('danger', __('Danger Color'), __('Used for destructive actions and errors.')),
                static::colorPicker('info', __('Info Color'), __('Used for informational notifications and highlights.')),
            ]);
    }

    protected static function surfacesSection(): Section
    {
        return Section::make(__('Surface & Interface'))
            ->description(__('Control backgrounds, cards, inputs, and borders for the dashboard.'))
            ->columns(2)
            ->schema([
                static::colorPicker('background', __('Dashboard Background'), __('Base canvas background color.')),
                static::colorPicker('surface', __('Surface / Card Background'), __('Background for cards, sections, and tables.')),
                static::colorPicker('input', __('Input Background'), __('Background for form inputs and text fields.')),
                static::colorPicker('border', __('Border Color'), __('Border lines for cards, dividers, and inputs.')),
            ]);
    }

    protected static function neutralPaletteSection(): Section
    {
        return Section::make(__('Neutral Palette'))
            ->description(__('Select a coherent neutral gray palette scale (50–950) for text, shadows, and subtle backgrounds.'))
            ->schema([
                Select::make('neutral_palette_preset')
                    ->label(__('Neutral Palette Preset'))
                    ->placeholder(__('Choose a neutral scale...'))
                    ->options(static::translatedOptions('neutral_palettes'))
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($shades = config("filament-studio.neutral_palettes.{$state}.shades")) {
                            static::setGrayPalette($set, $shades);
                        }
                    })
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Section::make(__('Advanced Gray Palette'))
                    ->description(__('Fine-tune individual shades from 50 to 950.'))
                    ->collapsed()
                    ->compact()
                    ->columns(4)
                    ->schema(array_map(
                        fn (int $shade) => static::colorPicker("gray_palette.{$shade}", (string) $shade),
                        StudioTheme::GRAY_SHADES,
                    )),
            ]);
    }

    protected static function typographySection(): Section
    {
        return Section::make(__('Typography & Font Settings'))
            ->description(__('Choose the font family and base sizing scale for Arabic and English UI.'))
            ->columns(3)
            ->schema([
                Select::make('font_arabic')
                    ->label(__('Arabic Font'))
                    ->helperText(__('Used when the dashboard is in Arabic.'))
                    ->options(config('filament-studio.fonts.arabic', []))
                    ->in(array_keys(config('filament-studio.fonts.arabic', [])))
                    ->searchable()
                    ->required(),
                Select::make('font_latin')
                    ->label(__('English Font'))
                    ->helperText(__('Used when the dashboard is in English.'))
                    ->options(config('filament-studio.fonts.latin', []))
                    ->in(array_keys(config('filament-studio.fonts.latin', [])))
                    ->searchable()
                    ->required(),
                Select::make('font_size')
                    ->label(__('Dashboard Font Size'))
                    ->helperText(__('Scales the whole interface, including the sidebar.'))
                    ->options(static::translatedOptions('font_sizes'))
                    ->in(array_keys(config('filament-studio.font_sizes', [])))
                    ->required(),
            ]);
    }

    protected static function layoutSection(): Section
    {
        return Section::make(__('Layout'))
            ->description(__('Control spacing and the roundness of cards, inputs, and buttons.'))
            ->columns(2)
            ->schema([
                Select::make('density')
                    ->label(__('Density'))
                    ->options(static::translatedOptions('densities'))
                    ->in(array_keys(config('filament-studio.densities', [])))
                    ->required(),
                Select::make('radius')
                    ->label(__('Corner Radius'))
                    ->options(static::translatedOptions('radii'))
                    ->in(array_keys(config('filament-studio.radii', [])))
                    ->required(),
            ]);
    }

    protected static function darkModeSection(): Section
    {
        return Section::make(__('Dark Mode'))
            ->description(__('Choose how dark mode works and the colors used for dark surfaces.'))
            ->columns(2)
            ->schema([
                Select::make('dark_mode')
                    ->label(__('Dark Mode'))
                    ->options(static::translatedOptions('dark_modes'))
                    ->in(array_keys(config('filament-studio.dark_modes', [])))
                    ->live()
                    ->required(),
                Select::make('default_theme_mode')
                    ->label(__('Default Mode'))
                    ->helperText(__('Used until a user picks a mode themselves.'))
                    ->options(static::translatedOptions('default_theme_modes'))
                    ->in(array_keys(config('filament-studio.default_theme_modes', [])))
                    ->visible(fn (Get $get): bool => $get('dark_mode') === 'toggle')
                    ->required(),

                Section::make(__('Dark Surfaces'))
                    ->compact()
                    ->columns(4)
                    ->visible(fn (Get $get): bool => $get('dark_mode') !== 'light')
                    ->schema([
                        static::colorPicker('dark_background', __('Dashboard Background')),
                        static::colorPicker('dark_surface', __('Surface / Card Background')),
                        static::colorPicker('dark_input', __('Input Background')),
                        static::colorPicker('dark_border', __('Border Color')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function colorPicker(string $name, string $label, ?string $helperText = null): ColorPicker
    {
        return ColorPicker::make($name)
            ->label($label)
            ->helperText($helperText)
            ->regex(StudioTheme::HEX_PATTERN)
            ->required();
    }

    /**
     * Options from a config list whose entries are either a label string or an array with a 'label'.
     *
     * @return array<string, string>
     */
    protected static function translatedOptions(string $configList): array
    {
        return collect(config("filament-studio.{$configList}", []))
            ->map(fn (array|string $item) => __(is_array($item) ? $item['label'] : $item))
            ->all();
    }

    /**
     * @param  array<int, string>  $shades
     */
    protected static function setGrayPalette(Set $set, array $shades): void
    {
        $set('gray_palette', $shades);

        foreach ($shades as $shade => $hex) {
            $set("gray_palette.{$shade}", $hex);
        }
    }
}
