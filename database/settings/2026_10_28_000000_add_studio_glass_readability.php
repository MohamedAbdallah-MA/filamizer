<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['glass_auto_readability', 'dark_glass_auto_readability'] as $key) {
            if (! DB::table('settings')->where('group', 'studio')->where('name', $key)->exists()) {
                $this->migrator->add("studio.{$key}", true);
            }
        }
        // Existing profiles and personal overrides remain unchanged; absent keys inherit the default.
    }

    public function down(): void
    {
        foreach (['glass_auto_readability', 'dark_glass_auto_readability'] as $key) {
            if (DB::table('settings')->where('group', 'studio')->where('name', $key)->exists()) {
                $this->migrator->delete("studio.{$key}");
            }
        }
    }
};
