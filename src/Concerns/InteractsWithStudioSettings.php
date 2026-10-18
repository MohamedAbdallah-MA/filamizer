<?php

namespace DealArt\FilamentStudio\Concerns;

use DealArt\FilamentStudio\Settings\StudioSettings;
use DealArt\FilamentStudio\Support\StudioTheme;
use Illuminate\Support\Arr;

/**
 * Lets any settings page host the StudioAppearanceTab: the tab's `studio`
 * state is filled from and saved to StudioSettings, separately from the
 * page's own settings class.
 */
trait InteractsWithStudioSettings
{
    protected bool $studioSettingsChanged = false;

    protected function fillStudioData(array $data): array
    {
        $data['studio'] = collect(StudioSettings::fields())
            ->mapWithKeys(fn (string $field) => [$field => StudioTheme::value($field)])
            ->all();

        return $data;
    }

    /**
     * Save the `studio` state and remove it, so the page's own settings never see it.
     */
    protected function saveStudioData(array $data): array
    {
        $studio = Arr::only($data['studio'] ?? [], StudioSettings::fields());
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
}
