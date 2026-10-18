<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (config('filament-studio.defaults') as $key => $value) {
            $this->migrator->add("studio.{$key}", $value);
        }
    }

    public function down(): void
    {
        foreach (array_keys(config('filament-studio.defaults')) as $key) {
            $this->migrator->delete("studio.{$key}");
        }
    }
};
