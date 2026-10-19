<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = [
        'preset', 'primary', 'success', 'warning', 'danger', 'info', 'gray_palette',
        'background', 'surface', 'input', 'border',
        'dark_background', 'dark_surface', 'dark_input', 'dark_border',
        'font_arabic', 'font_latin', 'font_size', 'density', 'radius',
        'dark_mode', 'default_theme_mode',
    ];

    public function up(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->add("studio.{$key}", config("filament-studio.defaults.{$key}"));
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->delete("studio.{$key}");
        }
    }
};
