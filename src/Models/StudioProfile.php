<?php

namespace Filamizer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved look. Profiles without a user are shared by the admin with everyone.
 */
class StudioProfile extends Model
{
    protected $table = 'studio_profiles';

    protected $fillable = ['user_id', 'name', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function isShared(): bool
    {
        return $this->user_id === null;
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Shared profiles plus the user's own.
     */
    public function scopeAvailableTo(Builder $query, int|string|null $userId): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $userId));
    }
}
