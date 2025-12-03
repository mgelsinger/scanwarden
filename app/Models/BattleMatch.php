<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BattleMatch extends Model
{
    protected $fillable = [
        'user_id',
        'attacker_team_id',
        'defender_team_id',
        'winner',
        'total_turns',
        'rating_change',
        'status',
        // Legacy PvP fields
        'attacker_id',
        'defender_id',
        'winner_id',
        'attacker_rating_before',
        'attacker_rating_after',
        'defender_rating_before',
        'defender_rating_after',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attackerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'attacker_team_id');
    }

    public function defenderTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'defender_team_id');
    }

    public function battleLogs(): HasMany
    {
        return $this->hasMany(BattleLog::class)->orderBy('turn_number');
    }

    // Legacy PvP relationships
    public function attacker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defender_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}
