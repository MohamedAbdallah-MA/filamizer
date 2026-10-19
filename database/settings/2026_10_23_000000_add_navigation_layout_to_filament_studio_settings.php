<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('studio.navigation_layout', []);
    }

    public function down(): void
    {
        $this->migrator->delete('studio.navigation_layout');
    }
};
