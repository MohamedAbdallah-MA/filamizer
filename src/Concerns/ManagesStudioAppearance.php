<?php

namespace DealArt\FilamentStudio\Concerns;

use DealArt\FilamentStudio\Models\StudioProfile;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
        return $profile->user_id === $this->studioProfileOwnerId()
            || ($profile->isShared() && $this->studioProfileOwnerId() === null);
    }

    public function applyStudioProfile(int $id): void
    {
        $profile = $this->getStudioProfiles()->firstWhere('id', $id);

        if (! $profile) {
            return;
        }

        $this->setStudioState(Arr::only($profile->data ?? [], $this->studioEditableKeys()));

        Notification::make()
            ->title(__('Profile ":name" applied. Save to keep it.', ['name' => $profile->name]))
            ->success()
            ->send();
    }

    public function deleteStudioProfile(int $id): void
    {
        $profile = $this->getStudioProfiles()->firstWhere('id', $id);

        if (! $profile || ! $this->canDeleteStudioProfile($profile)) {
            return;
        }

        $profile->delete();

        Notification::make()->title(__('Profile deleted'))->success()->send();
    }

    public function saveStudioProfile(string $name): void
    {
        $owner = $this->studioProfileOwnerId();
        $name = trim(strip_tags($name));

        if ($name === '') {
            return;
        }

        if ($owner !== null && StudioProfile::query()->where('user_id', $owner)->count() >= config('filament-studio.profiles.max_per_user', 20)) {
            Notification::make()->title(__('You have reached the profile limit. Delete one first.'))->danger()->send();

            return;
        }

        StudioProfile::query()->updateOrCreate(
            ['user_id' => $owner, 'name' => $name],
            ['data' => Arr::only($this->data['studio'] ?? [], $this->studioEditableKeys())],
        );

        Notification::make()->title(__('Profile ":name" saved', ['name' => $name]))->success()->send();
    }

    public function resetStudioAppearance(): void
    {
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
}
