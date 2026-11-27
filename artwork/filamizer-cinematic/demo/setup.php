<?php
$app = require __DIR__.'/bootstrap.php';
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
if (! Schema::hasTable('users')) {
    Schema::create('users', function ($t) {
        $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
        $t->boolean('can_manage_studio')->default(true); $t->rememberToken();
    });
    \Filamizer\Tests\Fixtures\User::create(['name' => 'Demo Creator', 'email' => 'creator@example.test', 'password' => bcrypt('filming-demo-only')]);
}
Artisan::call('filamizer:install', ['--migrate' => true, '--no-interaction' => true]);
echo Artisan::output();
