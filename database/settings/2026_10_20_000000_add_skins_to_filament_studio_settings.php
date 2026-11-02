<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = [
        'skin', 'sidebar_style', 'backdrop', 'backdrop_image',
        'glass_blur', 'glass_opacity', 'glow_intensity',
    ];

    public function up(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->add("studio.{$key}", config("filamizer.defaults.{$key}"));
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->delete("studio.{$key}");
        }
    }
};
