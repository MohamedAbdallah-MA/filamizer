<?php

namespace FilamentCustomizer\Models;

use Illuminate\Database\Eloquent\Model;

class StudioUserPreference extends Model
{
    protected $table = 'studio_user_preferences';

    protected $fillable = ['user_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}
