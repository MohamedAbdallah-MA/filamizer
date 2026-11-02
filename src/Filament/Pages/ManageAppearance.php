<?php

namespace Filamizer\Filament\Pages;

use BackedEnum;
use Filamizer\Concerns\InteractsWithStudioSettings;
use Filamizer\Filament\StudioAppearanceTab;
use Filamizer\Filament\StudioSidebarTab;
use Filamizer\Settings\StudioSettings;
use Filamizer\FilamizerPlugin;
use Filament\Facades\Filament;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/** The global editor for applications that do not have a settings page of their own. */
class ManageAppearance extends SettingsPage
{
    use InteractsWithStudioSettings;

    protected static string $settings = StudioSettings::class;

    protected static ?string $slug = 'filamizer';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    public static function canAccess(): bool
    {
        $plugin = FilamizerPlugin::get();

        return Filament::auth()->check()
            && ($plugin?->hasSettingsPage() ?? false)
            && $plugin->userCanCustomize();
    }

    public static function getNavigationLabel(): string
    {
        return __('Dashboard appearance');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Dashboard appearance');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('studio-settings')->tabs([
                StudioAppearanceTab::make(),
                StudioSidebarTab::make(),
            ])->columnSpanFull(),
        ]);
    }

    protected function fillForm(): void
    {
        $this->form->fill($this->fillStudioData([]));
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->saveStudioData($this->form->getState());
        $this->rememberData();
        $this->getSavedNotification()?->send();
        $this->reloadIfStudioSettingsChanged();
    }
}
