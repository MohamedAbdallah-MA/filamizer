<?php

namespace Filamizer\Tests\Feature;

use Filamizer\Models\StudioProfile;
use Filamizer\Settings\StudioSettings;
use Filamizer\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class InstallationTest extends TestCase
{
    public function test_fresh_install_uses_the_generic_indigo_preset_in_both_modes(): void
    {
        $this->install();
        $settings = app(StudioSettings::class)->refresh();
        $this->assertSame('royal_indigo', $settings->preset);
        $this->assertSame('royal_indigo', $settings->dark_preset);
        $this->assertSame('#4F46E5', $settings->primary);
        $this->assertSame('#4F46E5', $settings->dark_primary);
        $this->assertSame('#F9FAFB', $settings->background);
        $this->assertSame('#111827', $settings->dark_surface);
        $this->assertSame([
            'corporate_navy', 'royal_indigo', 'forest_emerald', 'sunset_crimson',
            'modern_purple', 'amber', 'slate',
        ], array_keys(config('filamizer.themes')));
    }

    public function test_installer_leaves_unrelated_application_migrations_pending(): void
    {
        $migration = database_path('migrations/2026_11_03_999999_studio_installation_sentinel.php');
        $this->assertFileDoesNotExist($migration);
        file_put_contents($migration, <<<'PHP'
<?php
return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void {
        \Illuminate\Support\Facades\Schema::create('unrelated_sentinel', function ($table) { $table->id(); });
    }
};
PHP);
        try {
            $this->install();
            $this->assertFalse(Schema::hasTable('unrelated_sentinel'));
            $this->assertTrue(Schema::hasTable('studio_profiles'));
        } finally {
            unlink($migration);
        }
    }

    public function test_default_install_publishes_the_runtime_stylesheet(): void
    {
        $this->artisan('filamizer:install', ['--no-interaction' => true])->assertSuccessful();
        $published = public_path('css/filamizer/filamizer/filamizer.css');
        $this->assertFileExists($published);
        $this->assertSame(hash_file('sha256', dirname(__DIR__, 2).'/resources/dist/filamizer.css'), hash_file('sha256', $published));
    }

    public function test_fresh_install_builds_its_storage_and_is_repeatable(): void
    {
        $this->assertFalse(Schema::hasTable('settings'));
        $this->install();

        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertTrue(Schema::hasTable('studio_user_preferences'));
        $this->assertTrue(Schema::hasTable('studio_profiles'));
        $settings = app(StudioSettings::class);
        $this->assertCount(count(StudioSettings::fields()), $settings->toArray());
        $settings->primary = '#123456';
        $settings->save();
        $profile = StudioProfile::create(['name' => 'Existing look', 'data' => ['primary' => '#123456']]);

        $this->install();

        $this->assertSame('#123456', app(StudioSettings::class)->refresh()->primary);
        $this->assertSame(['primary' => '#123456'], $profile->refresh()->data);
    }

    public function test_non_interactive_install_does_not_migrate_unless_requested(): void
    {
        $this->artisan('filamizer:install', ['--no-assets' => true, '--no-interaction' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('settings'));
    }

    public function test_unsupported_repository_returns_a_failure_instead_of_claiming_success(): void
    {
        config()->set('settings.repositories.database.table', 'custom_settings');
        $this->artisan('filamizer:install', [
            '--migrate' => true, '--no-assets' => true, '--no-interaction' => true,
        ])->assertFailed();
        $this->assertFalse(Schema::hasTable('studio_profiles'));
    }
}
