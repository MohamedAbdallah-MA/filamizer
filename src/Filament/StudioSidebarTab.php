<?php

namespace DealArt\FilamentStudio\Filament;

use DealArt\FilamentStudio\Filament\Components\NavigationLayoutEditor;
use DealArt\FilamentStudio\StudioPlugin;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

class StudioSidebarTab
{
    public static function make(): Tab
    {
        return Tab::make(__('Sidebar'))
            ->icon('heroicon-o-bars-3-bottom-left')
            ->statePath('studio')
            ->visible(fn (): bool => StudioPlugin::get()?->userCanCustomize() ?? false)
            ->schema([
                Section::make(__('Sidebar Builder'))
                    ->description(__('Drag items between groups, reorder groups, rename anything in Arabic and English, change icons, or hide items. Hiding only removes an item from the sidebar; permissions still decide who can open it.'))
                    ->schema([
                        NavigationLayoutEditor::make('navigation_layout')
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
