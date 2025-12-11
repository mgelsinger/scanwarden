<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SummonedUnit extends Model
{
    use HasFactory;

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
        'passive_key',
        'source',
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

    public function evolutionRuleForCurrentTier(): HasOne
    {
        return $this->hasOne(EvolutionRule::class, 'from_tier', 'tier');
    }
}
