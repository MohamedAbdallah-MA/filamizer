<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $keys = ['text_color', 'muted_text_color', 'dark_text_color', 'dark_muted_text_color'];

    public function up(): void
    {
        $light = json_decode((string) DB::table('settings')->where('group', 'studio')->where('name', 'gray_palette')->value('payload'), true);
        $dark = json_decode((string) DB::table('settings')->where('group', 'studio')->where('name', 'dark_gray_palette')->value('payload'), true);
        $existing = [
            'text_color' => $light[950] ?? null,
            'muted_text_color' => $light[500] ?? null,
            'dark_text_color' => '#FFFFFF',
            'dark_muted_text_color' => $dark[400] ?? null,
        ];

        foreach ($this->keys as $key) {
            $value = $existing[$key];
            if (! is_string($value) || ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                $value = config("filament-studio.defaults.{$key}");
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
