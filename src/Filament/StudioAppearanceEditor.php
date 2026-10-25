<?php

namespace DealArt\FilamentStudio\Filament;

use DealArt\FilamentStudio\Support\StudioPreferences;
use DealArt\FilamentStudio\Support\StudioTheme;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
 * choices, per theme; full customization reveals the precise controls in context.
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
            View::make('filament-studio::live-preview')->columnSpanFull(),

            Section::make(__('Profiles'))
                ->description(__('Your saved looks, including both light and dark modes. Apply one, then save when ready.'))
                ->extraAttributes(['class' => 'fi-studio-profile-section fi-studio-editor-controls'])
                ->headerActions([static::saveProfileAction()])
                ->schema([
                    View::make('filament-studio::profiles')
                        ->viewData(fn ($livewire) => [
                            'profiles' => $livewire->getStudioProfiles(),
                            'canDelete' => fn ($profile) => $livewire->canDeleteStudioProfile($profile),
                        ]),
                ])
                ->columnSpanFull(),

            Grid::make(['default' => 1, 'lg' => 5])
                ->extraAttributes(['class' => 'fi-studio-editor-grid'])
                ->schema([
                    Group::make([
                        Section::make(__('Make it yours'))
                            ->description(__('Start with a few choices. Open full customization whenever you want more control.'))
                            ->extraAttributes(['class' => 'fi-studio-editor-context'])
                            ->schema([
                                ToggleButtons::make('edit_mode')
                                    ->label(__('Mode to edit'))
                                    ->helperText(__('Skin, colors and background are saved separately for each mode.'))
                                    ->options(['light' => __('Light theme'), 'dark' => __('Dark theme')])
                                    ->icons(['light' => 'heroicon-o-sun', 'dark' => 'heroicon-o-moon'])
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('backdrop_palette', null);
                                        $set('neutral_palette_preset', null);
                                    })
                                    ->grouped()->inline()->live()->dehydrated(false),
                                ToggleButtons::make('show_advanced')
                                    ->label(__('Editing tools'))
                                    ->boolean()
                                    ->options([0 => __('Quick setup'), 1 => __('Full customization')])
                                    ->colors([0 => 'gray', 1 => 'gray'])
                                    ->icons([0 => 'heroicon-o-sparkles', 1 => 'heroicon-o-adjustments-horizontal'])
                                    ->default(false)->grouped()->inline()->live()->dehydrated(false),
                            ]),
                        static::skinSection($forUser),
                        static::backdropSection($forUser),
                        static::readingSection($forUser),
                        Group::make($forUser ? static::userAdvancedSchema() : static::adminAdvancedSchema())
                            ->visible(fn (Get $get): bool => (bool) $get('show_advanced'))
                            ->dehydratedWhenHidden(),
                        static::toolbar(),
                    ])->columnSpan(['lg' => 3])->extraAttributes(['class' => 'fi-studio-editor-controls']),
                    View::make('filament-studio::mini-preview')
                        ->viewData(['forUser' => $forUser])
                        ->columnSpan(['lg' => 2]),
                ]),
        ];
    }

    protected static function skinSection(bool $forUser): Section
    {
        // Controls on one side, a small dashboard drawn with the edited theme on the other.
        return Section::make(__('Style and color'))
            ->description(__('Choose the feel of your dashboard, then add your main color.'))
            ->icon('heroicon-o-swatch')
            ->schema([
                ...static::themePair('skin', fn (string $key) => ToggleButtons::make($key)
                    ->label(__('Skin'))
                    ->options(fn () => static::skinOptions($forUser, $key))
                    ->icons([
                        'classic' => 'heroicon-o-squares-2x2',
                        'glass' => 'heroicon-o-sparkles',
                        'neon' => 'heroicon-o-bolt',
                        'soft' => 'heroicon-o-cloud',
                        'minimal' => 'heroicon-o-minus',
                    ])
                    ->in(fn () => array_keys(static::skinOptions($forUser, $key)))
                    ->extraAttributes(['class' => 'fi-studio-skin-options'])
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($key): void {
                        // Glass over a plain color just looks like a flat card.
                        if ($state === 'glass' && $get($key === 'dark_skin' ? 'dark_backdrop' : 'backdrop') === 'none') {
                            $set($key === 'dark_skin' ? 'dark_backdrop' : 'backdrop', 'aurora');
                        }
                    })
                    ->dehydratedWhenHidden()
                    ->required()),

                Grid::make(2)->schema([
                    ...static::themePair('primary', fn (string $key) => static::colorPicker($key, __('Main color'))),
                    ...($forUser ? [] : static::presetFields()),
                ]),

                Grid::make(2)
                    ->visible(fn (Get $get): bool => (bool) $get('show_advanced'))
                    ->dehydratedWhenHidden()
                    ->extraAttributes(['class' => 'fi-studio-fine-tuning'])
                    ->schema([
                        ...static::themePair('text_color', fn (string $key) => static::colorPicker($key, __('Text color'))
                            ->helperText(__('Headings, content and all sidebar item labels.'))),
                        ...static::themePair('muted_text_color', fn (string $key) => static::colorPicker($key, __('Secondary text color'))
                            ->helperText(__('Descriptions, hints and sidebar group headings.'))),
                        ...static::themePair('surface', fn (string $key) => static::colorPicker($key, __('Cards and sidebar'))
                            ->label(fn (Get $get) => static::editingValue($get, 'skin') === 'glass' ? __('Glass tint') : __('Cards and sidebar'))),
                        ...static::themePair('glass_opacity', fn (string $key) => static::slider($key, __('Glass opacity')), fn (Get $get) => static::editingValue($get, 'skin') === 'glass'),
                        ...static::themePair('glass_blur', fn (string $key) => static::slider($key, __('Blur')), fn (Get $get) => static::editingValue($get, 'skin') === 'glass'),
                        ...static::themePair('glass_refraction', fn (string $key) => static::slider($key, __('Refraction'))
                            ->helperText(__('Refraction is visible in Chrome and Edge. Set to 0 to turn it off.')), fn (Get $get) => static::editingValue($get, 'skin') === 'glass'),
                        ...static::themePair('glow_intensity', fn (string $key) => static::slider($key, __('Glow intensity')), fn (Get $get) => static::editingValue($get, 'skin') === 'neon'),
                    ]),

            ]);
    }

    protected static function skinOptions(bool $forUser, string $key = 'skin'): array
    {
        $options = static::translatedOptions('skins');

        if (! $forUser) {
            return $options;
        }

        // The current global look remains available as inheritance, even when it
        // is not one of the skins a user is allowed to override for themselves.
        $global = StudioPreferences::make()->globalValue($key) ?? StudioTheme::default($key);

        return array_intersect_key($options, array_flip([...StudioPreferences::allowedSkins(), $global]));
    }

    protected static function toolbar(): Actions
    {
        return Actions::make([
            Action::make('copyStudioTheme')
                ->label(fn (Get $get): string => ($get('edit_mode') ?? 'light') === 'dark' ? __('Copy to light theme') : __('Copy to dark theme'))
                ->icon('heroicon-m-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Copy this mode’s style, text colors and effects to the other mode? Background, card, input and border colors stay separate. Save to keep the result.'))
                ->action(function (Get $get, Set $set): void {
                    $from = ($get('edit_mode') ?? 'light') === 'dark' ? 'dark' : 'light';
                    $to = $from === 'light' ? 'dark' : 'light';

                    // Keep surface colors independent when copying the rest of the look.
                    foreach (config("filament-studio.theme_keys.{$from}") as $key) {
                        if (! str_ends_with($key, 'background') && ! str_ends_with($key, 'surface') && ! str_ends_with($key, 'input') && ! str_ends_with($key, 'border')) {
                            $base = str_starts_with($key, 'dark_') ? substr($key, 5) : $key;
                            $set($to === 'dark' ? "dark_{$base}" : $base, $get($key));
                        }
                    }

                    $set('backdrop_palette', null);
                    $set('neutral_palette_preset', null);
                    $set('edit_mode', $to);
                }),

            Action::make('resetStudioAppearance')
                ->label(__('Reset to default'))
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Go back to the default look? Your saved profiles are kept. Nothing changes until you save.'))
                ->action(fn ($livewire) => $livewire->resetStudioAppearance()),

        ])->alignment(Alignment::Start);
    }

    protected static function saveProfileAction(): Action
    {
        return Action::make('saveStudioProfile')
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
            ->action(fn (array $data, $livewire) => $livewire->saveStudioProfile($data['name']));
    }

    /**
     * @return array<Component>
     */
    protected static function presetFields(): array
    {
        return [
            ...static::themePair('preset', fn (string $key) => Select::make($key)
                ->label(__('Starting palette'))
                ->helperText(__('Optional. Replaces colors in this mode only.'))
                ->placeholder(__('Choose a palette'))
                ->options(static::translatedOptions('themes'))
                ->in(array_keys(config('filament-studio.themes', [])))
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) use ($key): void {
                    $theme = config("filament-studio.themes.{$state}");

                    if (! $theme) {
                        return;
                    }

                    $prefix = $key === 'dark_preset' ? 'dark_' : '';
                    foreach (StudioTheme::SEMANTIC_COLORS as $color) {
                        $set($prefix.$color, $theme[$color]);
                    }
                    foreach (['background' => 950, 'surface' => 900, 'input' => 900, 'border' => 800] as $surface => $shade) {
                        $set($prefix.$surface, $prefix ? $theme['gray'][$shade] : $theme[$surface]);
                    }
                    static::setGrayPalette($set, $theme['gray'], $prefix);
                    $set($prefix.'text_color', $prefix ? '#FFFFFF' : $theme['gray'][950]);
                    $set($prefix.'muted_text_color', $theme['gray'][$prefix ? 400 : 500]);
                })
                ->dehydratedWhenHidden()),
        ];
    }

    protected static function readingSection(bool $forUser): Section
    {
        return Section::make(__('Reading and layout'))
            ->description(__('Shared by light and dark modes.'))
            ->icon('heroicon-o-language')
            ->schema([
                ToggleButtons::make('font_size')
                    ->label(__('Text size'))
                    ->options(['sm' => __('Small'), 'md' => __('Standard'), 'lg' => __('Large'), 'xl' => __('Extra large')])
                    ->in(array_keys(config('filament-studio.font_sizes', [])))
                    ->inline()->dehydratedWhenHidden()->required(),
                ...($forUser ? [] : [
                    Grid::make(2)
                        ->visible(fn (Get $get): bool => (bool) $get('show_advanced'))
                        ->dehydratedWhenHidden()
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
                ]),
            ]);
    }

    protected static function adminAdvancedSchema(): array
    {
        return [
            static::statusColorsSection(),
            static::inputsSection(),

            Section::make(__('Neutral Palette'))
                ->description(__('Select a coherent neutral gray palette scale (50–950) for text, shadows, and subtle backgrounds.'))
                ->collapsible()->collapsed()->compact()->dehydratedWhenHidden()
                ->schema([
                    Select::make('neutral_palette_preset')
                        ->label(__('Neutral Palette Preset'))
                        ->placeholder(__('Choose a neutral scale...'))
                        ->options(static::translatedOptions('neutral_palettes'))
                        ->live()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            if ($shades = config("filament-studio.neutral_palettes.{$state}.shades")) {
                                static::setGrayPalette($set, $shades, ($get('edit_mode') ?? 'light') === 'dark' ? 'dark_' : '');
                            }
                        })
                        ->dehydrated(false),
                    Grid::make(4)->dehydratedWhenHidden()->schema(collect(StudioTheme::GRAY_SHADES)->flatMap(
                        fn (int $shade) => static::themePair("gray_palette.{$shade}", fn (string $key) => static::colorPicker($key, (string) $shade)),
                    )->all()),
                ]),

            Section::make(__('Dark mode and personal looks'))
                ->collapsible()->collapsed()->compact()->dehydratedWhenHidden()
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
        ];
    }

    protected static function statusColorsSection(): Section
    {
        return Section::make(__('Status colors'))
            ->description(__('Colors for success, warning, danger, and information.'))
            ->collapsible()->collapsed()->compact()->dehydratedWhenHidden()
            ->columns(4)
            ->schema([
                ...static::themePair('success', fn (string $key) => static::colorPicker($key, __('Success Color'))),
                ...static::themePair('warning', fn (string $key) => static::colorPicker($key, __('Warning Color'))),
                ...static::themePair('danger', fn (string $key) => static::colorPicker($key, __('Danger Color'))),
                ...static::themePair('info', fn (string $key) => static::colorPicker($key, __('Info Color'))),
            ]);
    }

    protected static function inputsSection(): Section
    {
        return Section::make(__('Inputs and borders'))
            ->collapsible()->collapsed()->compact()->dehydratedWhenHidden()
            ->columns(2)
            ->schema([
                ...static::themePair('input', fn (string $key) => static::colorPicker($key, __('Input Background'))),
                ...static::themePair('border', fn (string $key) => static::colorPicker($key, __('Border Color'))),
            ]);
    }

    protected static function backdropOptions(bool $forUser, string $key): array
    {
        $options = static::translatedOptions('backdrops');
        if ($forUser && StudioPreferences::make()->globalValue($key) !== 'image') {
            unset($options['image']);
        }

        return $options;
    }

    public static function editingValue(Get $get, string $key): mixed
    {
        return $get(($get('edit_mode') === 'dark' ? 'dark_' : '').$key);
    }

    protected static function backdropSection(bool $forUser): Section
    {
        return Section::make(__('Page background'))
            ->description($forUser ? __('Choose a solid color or a ready-made blend.') : __('Choose a solid color, a ready-made blend or your own image.'))
            ->icon('heroicon-o-photo')
            ->columns(2)
            ->schema([
                ...static::themePair('backdrop', fn (string $key) => ToggleButtons::make($key)
                    ->label(__('Backdrop'))
                    ->options(fn () => static::backdropOptions($forUser, $key))
                    ->extraAttributes(['class' => 'fi-studio-backdrop-options'])
                    ->in(fn () => array_keys(static::backdropOptions($forUser, $key)))
                    ->inline()->live()->dehydratedWhenHidden()->required()
                    ->columnSpanFull()),
                ...static::themePair('background', fn (string $key) => static::colorPicker($key, __('Base background color'))),
                ToggleButtons::make('backdrop_palette')
                    ->label(__('Backdrop palette'))
                    ->options(static::translatedOptions('backdrop_palettes'))
                    ->inline()->live()->dehydrated(false)
                    ->extraAttributes(['class' => 'fi-studio-backdrop-presets'])
                    ->visible(fn (Get $get) => in_array(static::editingValue($get, 'backdrop'), ['gradient', 'mesh', 'aurora'], true))
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $colors = config("filament-studio.backdrop_palettes.{$state}.colors");

                        if (! is_array($colors)) {
                            return;
                        }

                        $prefix = ($get('edit_mode') ?? 'light') === 'dark' ? 'dark_' : '';

                        foreach ($colors as $index => $color) {
                            $set($prefix.'backdrop_color_'.($index + 1), $color);
                        }
                    })
                    ->columnSpanFull(),
                ...static::themePair('backdrop_color_1', fn (string $key) => static::colorPicker($key, __('First color'))
                    ->afterStateUpdated(fn (Set $set) => $set('backdrop_palette', null)), fn (Get $get) => $get('show_advanced') && in_array(static::editingValue($get, 'backdrop'), ['gradient', 'mesh', 'aurora'], true)),
                ...static::themePair('backdrop_color_2', fn (string $key) => static::colorPicker($key, __('Second color'))
                    ->afterStateUpdated(fn (Set $set) => $set('backdrop_palette', null)), fn (Get $get) => $get('show_advanced') && in_array(static::editingValue($get, 'backdrop'), ['gradient', 'mesh', 'aurora'], true)),
                ...static::themePair('backdrop_color_3', fn (string $key) => static::colorPicker($key, __('Third color'))
                    ->afterStateUpdated(fn (Set $set) => $set('backdrop_palette', null)), fn (Get $get) => $get('show_advanced') && in_array(static::editingValue($get, 'backdrop'), ['mesh', 'aurora'], true)),
                ...static::themePair('backdrop_angle', fn (string $key) => static::slider($key, __('Gradient direction')), fn (Get $get) => $get('show_advanced') && static::editingValue($get, 'backdrop') === 'gradient'),
                ...static::themePair('aurora_animated', fn (string $key) => Toggle::make($key)
                    ->label(__('Animate Aurora'))
                    ->live()->dehydratedWhenHidden()
                    ->helperText(__('Reduced motion on your device takes priority over animation.')), fn (Get $get) => static::editingValue($get, 'backdrop') === 'aurora'),
                ...static::themePair('aurora_speed', fn (string $key) => static::slider($key, __('Animation duration (seconds)'))
                    ->helperText(__('Lower values move faster.')), fn (Get $get) => $get('show_advanced') && static::editingValue($get, 'backdrop') === 'aurora' && static::editingValue($get, 'aurora_animated')),
                ...static::themePair('backdrop_intensity', fn (string $key) => static::slider($key, __('Backdrop intensity')), fn (Get $get) => $get('show_advanced') && static::editingValue($get, 'backdrop') !== 'none'),
                ...($forUser ? [] : [
                    ...static::themePair('backdrop_image', fn (string $key) => FileUpload::make($key)
                        ->label(__('Backdrop Image'))
                        ->helperText(__('Shown after saving. Large, soft images work best.'))
                        ->image()
                        ->disk(config('filament-studio.backdrop_disk'))
                        ->directory(config('filament-studio.backdrop_directory'))
                        ->visibility('public')
                        ->maxSize(4096)
                        ->required(fn (Get $get): bool => $get($key === 'dark_backdrop_image' ? 'dark_backdrop' : 'backdrop') === 'image')
                        ->dehydratedWhenHidden()
                        ->columnSpanFull(), fn (Get $get): bool => static::editingValue($get, 'backdrop') === 'image'),
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
        return in_array(static::editingValue($get, 'skin') ?? 'classic', static::SOLID_SKINS, true);
    }

    protected static function colorPicker(string $name, string $label): ColorPicker
    {
        return ColorPicker::make($name)
            ->label($label)
            ->extraInputAttributes(['dir' => 'ltr'])
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
            ->decimalPlaces(0)
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
    protected static function setGrayPalette(Set $set, array $shades, string $prefix = ''): void
    {
        $set($prefix.'gray_palette', $shades);

        foreach ($shades as $shade => $hex) {
            $set("{$prefix}gray_palette.{$shade}", $hex);
        }
    }
}
