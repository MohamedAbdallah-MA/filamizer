<?php

namespace DealArt\FilamentStudio\Concerns;

use DealArt\FilamentStudio\Settings\StudioSettings;
use DealArt\FilamentStudio\Support\StudioPreferences;
use DealArt\FilamentStudio\Support\StudioTheme;
use Illuminate\Support\Arr;

/**
 * Lets any settings page host the admin appearance editor: the tab's `studio`
 * state is filled from and saved to StudioSettings (everyone's default look),
 * separately from the page's own settings class.
 */
trait InteractsWithStudioSettings
{
    use ManagesStudioAppearance;

    protected bool $studioSettingsChanged = false;

    protected function fillStudioData(array $data): array
    {
        // The admin edits the shared defaults, never their own personal look.
        $data['studio'] = [
            ...collect(StudioSettings::fields())
                ->mapWithKeys(fn (string $field) => [$field => StudioPreferences::make()->globalValue($field) ?? StudioTheme::default($field)])
                ->all(),
            ...$this->studioEditorState(),
        ];

        return $data;
    }

    /**
     * Save the `studio` state and remove it, so the page's own settings never see it.
     */
    protected function saveStudioData(array $data): array
    {
        $studio = StudioTheme::sanitizeValues(Arr::only($data['studio'] ?? [], StudioSettings::fields()));
        unset($data['studio']);

        if ($studio === []) {
            return $data;
        }

        $settings = app(StudioSettings::class);
        $before = $settings->toArray();

        $settings->fill($studio)->save();

        $this->studioSettingsChanged = $before != $settings->toArray();

        return $data;
    }

    /**
     * Reload so the server renders the exact palette after a change.
     */
    protected function reloadIfStudioSettingsChanged(): void
    {
        if ($this->studioSettingsChanged) {
            $this->js('window.location.reload()');
        }
    }

    protected function studioEditableKeys(): array
    {
        return array_values(array_diff(
            StudioSettings::fields(),
            ['navigation_layout', 'user_skins', 'dark_mode', 'default_theme_mode'],
        ));
    }

    protected function studioProfileOwnerId(): int|string|null
    {
        return null;
    }

    protected function studioResetValues(): array
    {
        return Arr::only(config('filament-studio.defaults', []), $this->studioEditableKeys());
    }
}
