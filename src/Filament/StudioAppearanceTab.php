<?php

namespace FilamentCustomizer\Filament;

use FilamentCustomizer\CustomizerPlugin;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * The admin's appearance tab: everyone's default look.
 */
class StudioAppearanceTab
{
    public static function make(): Tab
    {
        return Tab::make(__('Dashboard Appearance & Design System'))
            ->icon('heroicon-o-swatch')
            ->statePath('studio')
            ->visible(fn (): bool => CustomizerPlugin::get()?->userCanCustomize() ?? false)
            ->schema(StudioAppearanceEditor::components(forUser: false));
    }
}
