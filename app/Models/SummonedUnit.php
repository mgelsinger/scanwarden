<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SummonedUnit extends Model
{
    protected $fillable = [
        'user_id',
        'sector_id',
        'name',
        'rarity',
        'tier',
        'evolution_level',
        'hp',
        'attack',
        'defense',
        'speed',
        'passive_ability',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_units')
            ->withPivot('position')
            ->withTimestamps();
    }
}
