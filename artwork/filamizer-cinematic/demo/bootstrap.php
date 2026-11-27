<?php
// Isolated filming host: fictional identity, SQLite, loopback server only.
require dirname(__DIR__, 3).'/vendor/autoload.php';

class FilmingApplication extends \Orchestra\Testbench\Foundation\Application
{
    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'app.key' => 'base64:'.base64_encode(str_repeat('f', 32)),
            'app.name' => 'Filamizer', 'app.url' => 'http://127.0.0.1:8127',
            'app.debug' => true, 'app.env' => 'local',
            'database.default' => 'filming',
            'database.connections.filming' => ['driver' => 'sqlite', 'database' => __DIR__.'/demo.sqlite', 'prefix' => ''],
            'auth.providers.users.model' => \Filamizer\Tests\Fixtures\User::class,
            'auth.guards.studio' => ['driver' => 'session', 'provider' => 'users'],
            'session.driver' => 'file',
            'settings.cache.enabled' => false,
            'settings.auto_discover_settings' => [],
        ]);
    }
}

$providers = [
    \BladeUI\Icons\BladeIconsServiceProvider::class,
    \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
    \Livewire\LivewireServiceProvider::class,
    \RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider::class,
    \Kirschbaum\PowerJoins\PowerJoinsServiceProvider::class,
    \Filament\Support\SupportServiceProvider::class,
    \Filament\Actions\ActionsServiceProvider::class,
    \Filament\Forms\FormsServiceProvider::class,
    \Filament\Infolists\InfolistsServiceProvider::class,
    \Filament\Schemas\SchemasServiceProvider::class,
    \Filament\Tables\TablesServiceProvider::class,
    \Filament\Widgets\WidgetsServiceProvider::class,
    \Filament\Notifications\NotificationsServiceProvider::class,
    \Filament\FilamentServiceProvider::class,
    \Spatie\LaravelSettings\LaravelSettingsServiceProvider::class,
    \Filament\SpatieLaravelSettingsPluginServiceProvider::class,
    \Filamizer\FilamizerServiceProvider::class,
    \Filamizer\Tests\Fixtures\PanelProvider::class,
];
$app = FilmingApplication::create(__DIR__.'/host', options: ['extra' => ['providers' => $providers, 'dont-discover' => ['*']]]);
$app['router']->middleware('web')->get('/demo-login', function () {
    \Illuminate\Support\Facades\Auth::guard('studio')->loginUsingId(1);
    return redirect('/studio/filamizer');
});
return $app;
