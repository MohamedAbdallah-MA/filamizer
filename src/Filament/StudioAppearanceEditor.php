<?php

namespace DealArt\FilamentStudio\Filament;

use DealArt\FilamentStudio\Support\StudioPreferences;
use DealArt\FilamentStudio\Support\StudioTheme;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Alignment;

/**
 * The appearance editor, shared by the admin tab (everyone's default look) and the
 * "My appearance" page (a user's own look). A short simple view covers everyday
 * choices, per theme; everything else sits behind "Show advanced settings".
 *
 * Fields are kept when hidden (dehydratedWhenHidden), so switching between the
 * light and dark theme, or between skins, never drops unsaved changes.
 */
class StudioAppearanceEditor
{
    public const SOLID_SKINS = ['classic', 'soft', 'minimal'];

    /**
     * @return array<Component>
     */
    public static function components(bool $forUser = false): array
    {
        return [
            Hidden::make('show_advanced')->default(false)->dehydrated(false),

            View::make('filament-studio::live-preview')->columnSpanFull(),

            View::make('filament-studio::profiles')
                ->viewData(fn ($livewire) => [
                    'profiles' => $livewire->getStudioProfiles(),
                    'canDelete' => fn ($profile) => $livewire->canDeleteStudioProfile($profile),
                ])
                ->columnSpanFull(),

            static::simpleSection($forUser),

            static::toolbar(),

            Section::make(__('Advanced settings'))
                ->visible(fn (Get $get): bool => (bool) $get('show_advanced'))
                ->schema($forUser ? static::userAdvancedSchema() : static::adminAdvancedSchema()),
        ];
    }

    protected static function simpleSection(bool $forUser): Section
    {
        // Controls on one side, a small dashboard drawn with the edited theme on the other.
        return Section::make()
            ->schema([Grid::make(['default' => 1, 'lg' => 5])->schema([Group::make([
                ToggleButtons::make('skin')
                    ->label(__('Skin'))
                    ->options(fn () => collect(static::translatedOptions('skins'))
                        ->only($forUser ? StudioPreferences::allowedSkins() : array_keys(config('filament-studio.skins', [])))
                        ->all())
                    ->icons([
                        'classic' => 'heroicon-o-squares-2x2',
                        'glass' => 'heroicon-o-sparkles',
                        'neon' => 'heroicon-o-bolt',
                        'soft' => 'heroicon-o-cloud',
                        'minimal' => 'heroicon-o-minus',
                    ])
                    ->in(fn () => $forUser ? StudioPreferences::allowedSkins() : array_keys(config('filament-studio.skins', [])))
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        // Glass over a plain color just looks like a flat card.
                        if ($state === 'glass' && $get('backdrop') === 'none') {
                            $set('backdrop', 'aurora');
                        }
                    })
                    ->helperText(fn (Get $get): string => in_array($get('skin'), static::SOLID_SKINS, true)
                        ? __('Solid skin: pick the page background, cards and main color for each theme.')
                        : __('Effect skin: pick the main color and tune the effect for each theme.'))
                    ->dehydratedWhenHidden()
                    ->required(),

                ToggleButtons::make('edit_mode')
                    ->label(__('Customize'))
                    ->options(['light' => __('Light theme'), 'dark' => __('Dark theme')])
                    ->icons(['light' => 'heroicon-o-sun', 'dark' => 'heroicon-o-moon'])
                    ->inline()
                    ->live()
                    ->dehydrated(false),

                Grid::make(2)->schema([
                    ...static::themePair('primary', fn (string $key) => static::colorPicker($key, __('Main color'))),
                    ...static::themePair('background', fn (string $key) => static::colorPicker($key, __('Page background')), fn (Get $get) => static::isSolid($get)),
                    ...static::themePair('surface', fn (string $key) => static::colorPicker($key, __('Cards and sidebar')), fn (Get $get) => static::isSolid($get)),
                    ...static::themePair('glass_opacity', fn (string $key) => static::slider($key, __('Glass opacity')), fn (Get $get) => $get('skin') === 'glass'),
                    ...static::themePair('glow_intensity', fn (string $key) => static::slider($key, __('Glow intensity')), fn (Get $get) => $get('skin') === 'neon'),
                ]),

                ToggleButtons::make('font_size')
                    ->label(__('Text size'))
                    ->options(static::translatedOptions('font_sizes'))
                    ->in(array_keys(config('filament-studio.font_sizes', [])))
                    ->inline()
                    ->dehydratedWhenHidden()
                    ->required(),
            ])->columnSpan(['lg' => 3]),

                View::make('filament-studio::mini-preview')->columnSpan(['lg' => 2]),
            ])]);
    }

    protected static function toolbar(): Actions
    {
        return Actions::make([
            Action::make('toggleStudioAdvanced')
                ->label(fn (Get $get): string => $get('show_advanced') ? __('Hide advanced settings') : __('Show advanced settings'))
                ->icon(fn (Get $get): string => $get('show_advanced') ? 'heroicon-m-chevron-up' : 'heroicon-m-adjustments-horizontal')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => $set('show_advanced', ! $get('show_advanced'))),

            Action::make('copyStudioTheme')
                ->label(fn (Get $get): string => ($get('edit_mode') ?? 'light') === 'dark' ? __('Copy to light theme') : __('Copy to dark theme'))
                ->icon('heroicon-m-document-duplicate')
                ->color('gray')
                ->action(function (Get $get, Set $set): void {
                    $from = ($get('edit_mode') ?? 'light') === 'dark' ? 'dark' : 'light';
                    $to = $from === 'light' ? 'dark' : 'light';
                    $toKeys = config("filament-studio.theme_keys.{$to}");

                    // Colors only: background and card colors rarely work in both themes.
                    foreach (config("filament-studio.theme_keys.{$from}") as $index => $key) {
                        if (! str_ends_with($key, 'background') && ! str_ends_with($key, 'surface') && ! str_ends_with($key, 'input') && ! str_ends_with($key, 'border')) {
                            $set($toKeys[$index], $get($key));
                        }
                    }

                    $set('edit_mode', $to);
                }),

            Action::make('resetStudioAppearance')
                ->label(__('Reset to default'))
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Go back to the default look? Your saved profiles are kept. Nothing changes until you save.'))
                ->action(fn ($livewire) => $livewire->resetStudioAppearance()),

            Action::make('saveStudioProfile')
                ->label(__('Save as profile'))
                ->icon('heroicon-m-bookmark')
                ->color('gray')
                ->schema([
                    TextInput::make('name')
                        ->label(__('Profile name'))
                        ->placeholder(__('Night shift'))
                        ->maxLength(config('filament-studio.profiles.name_max_length', 40))
                        ->required(),
                ])
                ->modalSubmitActionLabel(__('Save profile'))
                ->action(fn (array $data, $livewire) => $livewire->saveStudioProfile($data['name'])),
        ])->alignment(Alignment::Start);
    }

    /**
     * @return array<Component>
     */
    protected static function adminAdvancedSchema(): array
    {
        return [
            Section::make(__('Theme Presets'))
                ->description(__('Fill every color from a ready-made palette.'))
                ->collapsible()->collapsed()->compact()
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

                            // The dark theme follows the preset's own gray scale.
                            $set('dark_primary', $theme['primary']);
                            $set('dark_background', $theme['gray'][950]);
                            $set('dark_surface', $theme['gray'][900]);
                            $set('dark_input', $theme['gray'][900]);
                            $set('dark_border', $theme['gray'][800]);
                        })
                        ->dehydratedWhenHidden(),
                ]),

            static::statusColorsSection(),
            static::inputsSection(),
            static::effectsSection(forUser: false),

            Section::make(__('Neutral Palette'))
                ->description(__('Select a coherent neutral gray palette scale (50–950) for text, shadows, and subtle backgrounds.'))
                ->collapsible()->collapsed()->compact()
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
                        ->dehydrated(false),
                    Grid::make(4)->schema(array_map(
                        fn (int $shade) => static::colorPicker("gray_palette.{$shade}", (string) $shade),
                        StudioTheme::GRAY_SHADES,
                    )),
                ]),

            Section::make(__('Fonts and layout'))
                ->collapsible()->collapsed()->compact()
                ->columns(2)
                ->schema([
                    Select::make('font_arabic')
                        ->label(__('Arabic Font'))
                        ->options(config('filament-studio.fonts.arabic', []))
                        ->in(array_keys(config('filament-studio.fonts.arabic', [])))
                        ->searchable()->dehydratedWhenHidden()->required(),
                    Select::make('font_latin')
                        ->label(__('English Font'))
                        ->options(config('filament-studio.fonts.latin', []))
                        ->in(array_keys(config('filament-studio.fonts.latin', [])))
                        ->searchable()->dehydratedWhenHidden()->required(),
                    Select::make('density')
                        ->label(__('Density'))
                        ->options(static::translatedOptions('densities'))
                        ->in(array_keys(config('filament-studio.densities', [])))
                        ->dehydratedWhenHidden()->required(),
                    Select::make('radius')
                        ->label(__('Corner Radius'))
                        ->options(static::translatedOptions('radii'))
                        ->in(array_keys(config('filament-studio.radii', [])))
                        ->dehydratedWhenHidden()->required(),
                    ToggleButtons::make('sidebar_style')
                        ->label(__('Sidebar Style'))
                        ->helperText(__('"Collapsible to icons" takes effect after saving.'))
                        ->options(static::translatedOptions('sidebar_styles'))
                        ->in(array_keys(config('filament-studio.sidebar_styles', [])))
                        ->inline()->dehydratedWhenHidden()->required()
                        ->columnSpanFull(),
                ]),

            Section::make(__('Dark mode and personal looks'))
                ->collapsible()->collapsed()->compact()
                ->columns(2)
                ->schema([
                    Select::make('dark_mode')
                        ->label(__('Dark Mode'))
                        ->options(static::translatedOptions('dark_modes'))
                        ->in(array_keys(config('filament-studio.dark_modes', [])))
                        ->live()->dehydratedWhenHidden()->required(),
                    Select::make('default_theme_mode')
                        ->label(__('Default Mode'))
                        ->helperText(__('Used until a user picks a mode themselves.'))
                        ->options(static::translatedOptions('default_theme_modes'))
                        ->in(array_keys(config('filament-studio.default_theme_modes', [])))
                        ->dehydratedWhenHidden()->required(),
                    CheckboxList::make('user_skins')
                        ->label(__('Skins users can pick for themselves'))
                        ->helperText(__('Leave all unchecked to turn off "My appearance" for users.'))
                        ->options(static::translatedOptions('skins'))
                        ->in(array_keys(config('filament-studio.skins', [])))
                        ->columns(5)
                        ->dehydratedWhenHidden()
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @return array<Component>
     */
    protected static function userAdvancedSchema(): array
    {
        return [
            static::inputsSection(),
            static::effectsSection(forUser: true),
        ];
    }

    protected static function statusColorsSection(): Section
    {
        return Section::make(__('Status colors'))
            ->description(__('Colors for success, warning, danger, and information.'))
            ->collapsible()->collapsed()->compact()
            ->columns(4)
            ->schema([
                static::colorPicker('success', __('Success Color')),
                static::colorPicker('warning', __('Warning Color')),
                static::colorPicker('danger', __('Danger Color')),
                static::colorPicker('info', __('Info Color')),
            ]);
    }

    protected static function inputsSection(): Section
    {
        return Section::make(__('Inputs and borders'))
            ->collapsible()->collapsed()->compact()
            ->columns(2)
            ->schema([
                ...static::themePair('input', fn (string $key) => static::colorPicker($key, __('Input Background'))),
                ...static::themePair('border', fn (string $key) => static::colorPicker($key, __('Border Color'))),
            ]);
    }

    protected static function effectsSection(bool $forUser): Section
    {
        $backdrops = static::translatedOptions('backdrops');

        if ($forUser) {
            unset($backdrops['image']);
        }

        return Section::make(__('Glass and backdrop'))
            ->collapsible()->collapsed()->compact()
            ->columns(2)
            ->schema([
                static::slider('glass_blur', __('Blur'))
                    ->helperText(__('How strongly the background is blurred behind glass surfaces.')),
                static::slider('glass_refraction', __('Refraction'))
                    ->helperText(__('How much the glass edges bend what is behind them, like a lens. Shown in Chrome and Edge; other browsers keep the frosted look. 0 turns it off.')),
                ToggleButtons::make('backdrop')
                    ->label(__('Backdrop'))
                    ->helperText(__("Glass needs color behind it: Aurora looks most like Apple's Liquid Glass."))
                    ->options($backdrops)
                    ->in(array_keys($backdrops))
                    ->inline()->live()->dehydratedWhenHidden()->required()
                    ->columnSpanFull(),
                ...static::themePair('backdrop_intensity', fn (string $key) => static::slider($key, __('Backdrop intensity'))),
                ...($forUser ? [] : [
                    FileUpload::make('backdrop_image')
                        ->label(__('Backdrop Image'))
                        ->helperText(__('Shown after saving. Large, soft images work best.'))
                        ->image()
                        ->disk(config('filament-studio.backdrop_disk'))
                        ->directory(config('filament-studio.backdrop_directory'))
                        ->visibility('public')
                        ->maxSize(4096)
                        ->visible(fn (Get $get): bool => $get('backdrop') === 'image')
                        ->required(fn (Get $get): bool => $get('backdrop') === 'image')
                        ->dehydratedWhenHidden()
                        ->columnSpanFull(),
                ]),
            ]);
    }

    /**
     * The same setting for the light theme (plain key) and the dark theme (dark_ key);
     * only the theme being edited is shown.
     *
     * @return array<Component>
     */
    protected static function themePair(string $key, \Closure $make, ?\Closure $when = null): array
    {
        return collect([$key, "dark_{$key}"])
            ->map(fn (string $themeKey) => $make($themeKey)->visible(
                fn (Get $get): bool => static::isEditing($get, $themeKey) && (! $when || $when($get)),
            ))
            ->all();
    }

    public static function isEditing(Get $get, string $key): bool
    {
        $mode = ($get('edit_mode') ?? 'light') === 'dark' ? 'dark' : 'light';

        return str_starts_with($key, 'dark_') === ($mode === 'dark');
    }

    protected static function isSolid(Get $get): bool
    {
        return in_array($get('skin') ?? 'classic', static::SOLID_SKINS, true);
    }

    protected static function colorPicker(string $name, string $label): ColorPicker
    {
        return ColorPicker::make($name)
            ->label($label)
            ->regex(StudioTheme::HEX_PATTERN)
            ->dehydratedWhenHidden()
            ->required();
    }

    protected static function slider(string $key, string $label): Slider
    {
        [$min, $max] = config("filament-studio.ranges.{$key}");

        return Slider::make($key)
            ->label($label)
            ->range($min, $max)
            ->step(1)
            ->tooltips()
            ->rules(['integer', "between:{$min},{$max}"])
            ->dehydratedWhenHidden()
            ->required();
    }

    /**
     * @return array<string, string>
     */
    public static function translatedOptions(string $configList): array
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
