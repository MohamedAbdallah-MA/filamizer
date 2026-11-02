<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = [
        'dark_primary', 'dark_glass_opacity', 'dark_backdrop_intensity', 'dark_glow_intensity', 'user_skins',
    ];

    public function up(): void
    {
        // Existing installs keep their look: the dark theme starts from the current main color.
        $primary = json_decode((string) DB::table('settings')->where('group', 'studio')->where('name', 'primary')->value('payload'), true);

        foreach ($this->keys as $key) {
            $value = $key === 'dark_primary' && is_string($primary) ? $primary : config("filament-customizer.defaults.{$key}");

            $this->migrator->add("studio.{$key}", $value);
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->delete("studio.{$key}");
        }
    }
};
