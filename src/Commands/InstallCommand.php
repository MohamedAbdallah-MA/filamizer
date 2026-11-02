<?php

namespace Filamizer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

class InstallCommand extends Command
{
    protected $signature = 'filamizer:install
        {--force : Overwrite published config and allow migrations in production}
        {--migrate : Run Filamizer migrations without prompting}
        {--no-assets : Skip publishing Filament assets}';

    protected $description = 'Publish Filamizer configuration and assets, optionally migrate its storage';

    public function handle(): int
    {
        if ($this->call('vendor:publish', [
            '--tag' => 'filamizer-config',
            '--force' => (bool) $this->option('force'),
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $migrate = $this->option('migrate')
            || ($this->input->isInteractive() && $this->confirm('Run Filamizer migrations now?', true));

        if ($migrate && $this->migrateStudio() !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('no-assets') && $this->call('filament:assets') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Filamizer configured. Register FilamizerPlugin::make()->canCustomize(...) on your panel.');

        if (! $migrate) {
            $this->warn('Database migrations were skipped. Run filamizer:install --migrate before using the editors.');
        }

        return self::SUCCESS;
    }

    protected function migrateStudio(): int
    {
        // Historical Studio migrations use Spatie's default database table.
        if (config('settings.default_repository', 'database') !== 'database'
            || ! in_array(config('settings.repositories.database.table'), [null, 'settings'], true)) {
            $this->error('Filamizer currently requires the default database settings repository and settings table.');

            return self::FAILURE;
        }

        $paths = [__DIR__.'/../../database/settings', __DIR__.'/../../database/migrations'];

        if (! Schema::hasTable('settings')) {
            // Publish only Spatie's repository migration, not other vendors' files.
            if ($this->call('vendor:publish', [
                '--provider' => LaravelSettingsServiceProvider::class,
                '--tag' => 'migrations',
            ]) !== self::SUCCESS) {
                return self::FAILURE;
            }

            foreach (ServiceProvider::pathsToPublish(LaravelSettingsServiceProvider::class, 'migrations') as $path) {
                $paths[] = $path;
            }
        }

        // Explicit paths avoid running unrelated pending application migrations.
        return $this->call('migrate', [
            '--path' => $paths,
            '--realpath' => true,
            '--force' => (bool) $this->option('force'),
        ]);
    }
}
