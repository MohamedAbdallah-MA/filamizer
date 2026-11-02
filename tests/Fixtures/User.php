<?php

namespace Filamizer\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['can_manage_studio' => 'boolean'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
