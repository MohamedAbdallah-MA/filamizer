<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('studio.glass_refraction', config('filament-customizer.defaults.glass_refraction'));
    }

    public function down(): void
    {
        $this->migrator->delete('studio.glass_refraction');
    }
};
