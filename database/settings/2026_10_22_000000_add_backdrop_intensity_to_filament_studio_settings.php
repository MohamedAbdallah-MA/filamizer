<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('studio.backdrop_intensity', config('filament-customizer.defaults.backdrop_intensity'));
    }

    public function down(): void
    {
        $this->migrator->delete('studio.backdrop_intensity');
    }
};
