<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(SummonedUnit::class, 'team_units')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('team_units.position');
    }
}
