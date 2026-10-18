<?php

namespace DealArt\FilamentStudio\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filament-studio:install {--force : Overwrite the published config file}';

    protected $description = 'Publish the Filament Studio config and run its settings migration';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'filament-studio-config',
            '--force' => (bool) $this->option('force'),
        ]);

        if ($this->confirm('Run the settings migration now?', true)) {
            $this->call('migrate');
        }

        $this->info('Filament Studio installed. Register StudioPlugin::make() on your panel.');

        return self::SUCCESS;
    }
}
