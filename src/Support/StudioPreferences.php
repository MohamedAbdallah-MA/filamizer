<?php

namespace Filamizer\Support;

use Filamizer\Models\StudioUserPreference;
use Filamizer\Settings\StudioSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Arr;

/**
 * The effective appearance for the current request: the admin's settings, with
 * the signed-in user's own overrides on top. Bound as a scoped instance, so the
 * user's row is read once per request.
 */
class StudioPreferences
{
    protected ?array $overrides = null;

    protected int|string|null $overridesFor = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function value(string $key): mixed
    {
        $overrides = $this->userOverrides();

        if (array_key_exists($key, $overrides)) {
            return $overrides[$key];
        }

        return $this->globalValue($key);
    }

    public function globalValue(string $key): mixed
    {
        return rescue(fn () => app(StudioSettings::class)->{$key}, null, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function globalValues(): array
    {
        return collect(static::userKeys())
            ->mapWithKeys(fn (string $key) => [$key => $this->globalValue($key) ?? StudioTheme::default($key)])
            ->all();
    }

    /**
     * The current user's overrides, limited to the keys and skins the admin allows.
     *
     * @return array<string, mixed>
     */
    public function userOverrides(): array
    {
        $userId = rescue(fn () => Filament::auth()->id(), null, false);

        if ($this->overrides !== null && $this->overridesFor === $userId) {
            return $this->overrides;
        }

        $this->overridesFor = $userId;

        if (! $userId || ! static::isUserCustomizationEnabled()) {
            return $this->overrides = [];
        }

        $stored = rescue(fn () => StudioUserPreference::query()->where('user_id', $userId)->value('data'), null, false);

        return $this->overrides = static::cleanOverrides(is_array($stored) ? $stored : (json_decode((string) $stored, true) ?: []));
    }

    /**
     * Save a user's appearance, keeping only what differs from the admin's defaults.
     */
    public function saveForUser(int|string $userId, array $values): void
    {
        $global = $this->globalValues();

        $overrides = collect(StudioTheme::sanitizeValues(static::cleanOverrides($values)))
            ->reject(fn (mixed $value, string $key) => $value == ($global[$key] ?? null))
            ->all();

        if ($overrides === []) {
            StudioUserPreference::query()->where('user_id', $userId)->delete();
        } else {
            StudioUserPreference::query()->updateOrCreate(['user_id' => $userId], ['data' => $overrides]);
        }

        $this->forget();
    }

    public function forget(): void
    {
        $this->overrides = null;
        $this->overridesFor = null;
    }

    /**
     * @return array<int, string>
     */
    public static function userKeys(): array
    {
        return config('filamizer.user_keys', []);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedSkins(): array
    {
        $skins = rescue(fn () => app(StudioSettings::class)->user_skins, null, false) ?? StudioTheme::default('user_skins') ?? [];

        return array_values(array_intersect($skins, array_keys(config('filamizer.skins', []))));
    }

    public static function isUserCustomizationEnabled(): bool
    {
        return static::allowedSkins() !== [];
    }

    public static function cleanOverrides(array $values): array
    {
        $values = Arr::only($values, static::userKeys());

        foreach (['skin', 'dark_skin'] as $key) {
            if (isset($values[$key]) && ! in_array($values[$key], static::allowedSkins(), true)) {
                unset($values[$key]);
            }
        }

        return $values;
    }
}
