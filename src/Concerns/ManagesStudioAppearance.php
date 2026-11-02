<?php

namespace Filamizer\Concerns;

use Filamizer\Models\StudioProfile;
use Filamizer\Support\StudioPreferences;
use Filamizer\Support\StudioTheme;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Profiles and "reset to default" for a page that hosts the appearance editor
 * under the data.studio state path. The page decides the scope: which keys it
 * edits, whose profiles it sees, and what "default" means.
 */
trait ManagesStudioAppearance
{
    /**
     * @return array<int, string>
     */
    abstract protected function studioEditableKeys(): array;

    /**
     * Owner of profiles saved from this page; null = shared with everyone.
     */
    abstract protected function studioProfileOwnerId(): int|string|null;

    /**
     * @return array<string, mixed>
     */
    abstract protected function studioResetValues(): array;

    abstract protected function canManageStudioAppearance(): bool;

    /**
     * @return Collection<int, StudioProfile>
     */
    public function getStudioProfiles(): Collection
    {
        $owner = $this->studioProfileOwnerId();

        return StudioProfile::query()
            ->when($owner === null, fn ($query) => $query->shared(), fn ($query) => $query->availableTo($owner))
            ->orderByRaw('user_id is null desc')
            ->orderBy('name')
            ->get();
    }

    public function canDeleteStudioProfile(StudioProfile $profile): bool
    {
        return $this->canManageStudioAppearance()
            && ($profile->user_id === $this->studioProfileOwnerId()
                || ($profile->isShared() && $this->studioProfileOwnerId() === null));
    }

    public function applyStudioProfile(int $id): void
    {
        abort_unless($this->canManageStudioAppearance(), 403);

        $profile = $this->getStudioProfiles()->firstWhere('id', $id);

        if (! $profile) {
            return;
        }

        $values = StudioTheme::sanitizeValues(Arr::only($profile->data ?? [], $this->studioEditableKeys()));

        // Profiles created before text controls took their foregrounds from gray.
        foreach (['' => 'gray_palette', 'dark_' => 'dark_gray_palette'] as $prefix => $palette) {
            if (isset($values[$palette])) {
                $values[$prefix.'text_color'] ??= $prefix ? '#FFFFFF' : $values[$palette][950];
                $values[$prefix.'muted_text_color'] ??= $values[$palette][$prefix ? 400 : 500];
            }
        }

        if ($this->studioProfileOwnerId() !== null) {
            $values = StudioPreferences::cleanOverrides($values);
        }

        $this->setStudioState($values);

        Notification::make()
            ->title(__('Profile ":name" applied. Save to keep it.', ['name' => $profile->name]))
            ->success()
            ->send();
    }

    public function deleteStudioProfile(int $id): void
    {
        abort_unless($this->canManageStudioAppearance(), 403);

        $profile = $this->getStudioProfiles()->firstWhere('id', $id);

        if (! $profile || ! $this->canDeleteStudioProfile($profile)) {
            return;
        }

        $profile->delete();

        Notification::make()->title(__('Profile deleted'))->success()->send();
    }

    public function saveStudioProfile(string $name): void
    {
        abort_unless($this->canManageStudioAppearance(), 403);

        $owner = $this->studioProfileOwnerId();
        $name = mb_substr(trim(strip_tags($name)), 0, config('filamizer.profiles.name_max_length', 40));

        if ($name === '') {
            return;
        }

        $existing = StudioProfile::query()->where('user_id', $owner)->where('name', $name)->exists();

        if (! $existing && $owner !== null && StudioProfile::query()->where('user_id', $owner)->count() >= config('filamizer.profiles.max_per_user', 20)) {
            Notification::make()->title(__('You have reached the profile limit. Delete one first.'))->danger()->send();

            return;
        }

        StudioProfile::query()->updateOrCreate(
            ['user_id' => $owner, 'name' => $name],
            ['data' => StudioTheme::sanitizeValues(Arr::only($this->data['studio'] ?? [], $this->studioEditableKeys()))],
        );

        Notification::make()->title(__('Profile ":name" saved', ['name' => $name]))->success()->send();
    }

    public function resetStudioAppearance(): void
    {
        abort_unless($this->canManageStudioAppearance(), 403);

        $this->setStudioState($this->studioResetValues());

        Notification::make()->title(__('Default look restored. Save to keep it.'))->success()->send();
    }

    protected function setStudioState(array $values): void
    {
        $this->data['studio'] = array_replace($this->data['studio'] ?? [], $values);
    }

    /**
     * Editor-only state that is never saved.
     *
     * @return array<string, mixed>
     */
    protected function studioEditorState(): array
    {
        return ['edit_mode' => 'light', 'show_advanced' => false];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        // A saved value can be invalid even when its precise control is hidden
        // in Quick setup. Reveal the tools and mode containing the first error.
        foreach (array_keys($exception->errors()) as $path) {
            if (! str_starts_with($path, 'data.studio.')) {
                continue;
            }

            $this->data['studio']['show_advanced'] = true;
            $key = explode('.', substr($path, strlen('data.studio.')))[0];
            foreach (['light', 'dark'] as $mode) {
                if (in_array($key, config("filamizer.theme_keys.{$mode}", []), true)) {
                    $this->data['studio']['edit_mode'] = $mode;
                    break;
                }
            }

            break;
        }

        parent::onValidationError($exception);
    }
}
