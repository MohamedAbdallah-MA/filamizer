<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = [
        'backdrop_color_1', 'backdrop_color_2', 'backdrop_color_3',
        'dark_backdrop_color_1', 'dark_backdrop_color_2', 'dark_backdrop_color_3',
        'backdrop_angle', 'dark_backdrop_angle', 'aurora_speed', 'dark_aurora_speed', 'aurora_animated',
    ];

    public function up(): void
    {
        // Start from the colors already used by the install's backdrop.
        $sources = [
            'backdrop_color_1' => 'primary', 'dark_backdrop_color_1' => 'dark_primary',
            'backdrop_color_2' => 'info', 'dark_backdrop_color_2' => 'info',
            'backdrop_color_3' => 'success', 'dark_backdrop_color_3' => 'success',
        ];

        foreach ($this->keys as $key) {
            $value = config("filament-studio.defaults.{$key}");

            if (isset($sources[$key])) {
                $stored = json_decode((string) DB::table('settings')->where('group', 'studio')->where('name', $sources[$key])->value('payload'), true);

                if (is_string($stored) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $stored)) {
                    $value = $stored;
                }
            }

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
