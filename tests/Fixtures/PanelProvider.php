<?php

namespace FilamentCustomizer\Tests\Fixtures;

use FilamentCustomizer\CustomizerPlugin;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider as BasePanelProvider;

class PanelProvider extends BasePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('studio')->path('studio')->authGuard('studio')
            ->middleware(['web'])
            ->pages([Dashboard::class])
            ->plugin(CustomizerPlugin::make()->canCustomize(
                fn (): bool => (bool) Filament::auth()->user()?->can_manage_studio,
            ));
    }
}
