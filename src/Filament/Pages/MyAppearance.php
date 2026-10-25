<?php

namespace DealArt\FilamentStudio\Filament\Pages;

use DealArt\FilamentStudio\Concerns\ManagesStudioAppearance;
use DealArt\FilamentStudio\Filament\StudioAppearanceEditor;
use DealArt\FilamentStudio\Settings\StudioSettings;
use DealArt\FilamentStudio\StudioPlugin;
use DealArt\FilamentStudio\Support\StudioPreferences;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;

/**
 * A user's own look: the admin's default appearance, with their overrides on top.
 * Opened from the user menu; only available while the admin allows user skins.
 */
class MyAppearance extends SettingsPage
{
    use ManagesStudioAppearance;

    protected static string $settings = StudioSettings::class;

    protected static ?string $slug = 'my-appearance';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->check()
            && StudioPlugin::get() !== null
            && StudioPreferences::isUserCustomizationEnabled();
    }

    public function getTitle(): string|Htmlable
    {
        return __('My appearance');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Only changes how the dashboard looks for you.');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make(StudioAppearanceEditor::components(forUser: true))
                ->statePath('studio')
                ->columnSpanFull(),
        ]);
    }

    protected function fillForm(): void
    {
        $preferences = StudioPreferences::make();

        $this->form->fill([
            'studio' => [
                ...collect(StudioPreferences::userKeys())->mapWithKeys(fn (string $key) => [$key => $preferences->value($key)])->all(),
                ...$this->studioEditorState(),
            ],
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        StudioPreferences::make()->saveForUser(auth()->id(), Arr::only($data['studio'] ?? [], StudioPreferences::userKeys()));

        $this->rememberData();
        $this->getSavedNotification()?->send();

        // Reload so the server renders the new look everywhere on the page.
        $this->js('window.location.reload()');
    }

    protected function studioEditableKeys(): array
    {
        return StudioPreferences::userKeys();
    }

    protected function studioProfileOwnerId(): int|string|null
    {
        return auth()->id();
    }

    protected function canManageStudioAppearance(): bool
    {
        return static::canAccess();
    }

    protected function studioResetValues(): array
    {
        // A user's default is whatever the admin set for everyone.
        return StudioPreferences::make()->globalValues();
    }
}
