<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = [
        'skin', 'backdrop', 'backdrop_image', 'glass_blur', 'glass_refraction', 'aurora_animated',
        'preset', 'success', 'warning', 'danger', 'info', 'gray_palette',
    ];

    public function up(): void
    {
        foreach ($this->keys as $key) {
            $row = DB::table('settings')->where('group', 'studio')->where('name', $key)->first();
            $value = $row ? json_decode($row->payload, true) : config("filament-studio.defaults.{$key}");
            $this->migrator->add("studio.dark_{$key}", $value);
        }

        // Old profiles and overrides used these keys for both modes. Keep that meaning.
        foreach (['studio_profiles', 'studio_user_preferences'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->orderBy('id')->each(function ($row) use ($table): void {
                $values = json_decode($row->data, true);
                if (! is_array($values)) {
                    return;
                }
                foreach ($this->keys as $key) {
                    if (array_key_exists($key, $values) && ! array_key_exists("dark_{$key}", $values)) {
                        $values["dark_{$key}"] = $values[$key];
                    }
                }
                DB::table($table)->where('id', $row->id)->update(['data' => json_encode($values)]);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->delete("studio.dark_{$key}");
        }
    }
};
