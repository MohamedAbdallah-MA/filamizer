<?php

namespace Filamizer\Filament;

use Filamizer\FilamizerPlugin;
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
            ->visible(fn (): bool => FilamizerPlugin::get()?->userCanCustomize() ?? false)
            ->schema(StudioAppearanceEditor::components(forUser: false));
    }
}
