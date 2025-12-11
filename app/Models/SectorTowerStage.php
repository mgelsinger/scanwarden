<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectorTowerStage extends Model
{
    protected $fillable = [
        'tower_id',
        'floor',
        'enemy_team',
        'recommended_power',
        'rewards',
        'is_active',
    ];

    protected $casts = [
        'enemy_team' => 'array',
        'rewards' => 'array',
        'is_active' => 'boolean',
        'floor' => 'integer',
        'recommended_power' => 'integer',
    ];

    /**
     * Get the tower that owns the stage.
     */
    public function tower(): BelongsTo
    {
        return $this->belongsTo(SectorTower::class, 'tower_id');
    }
}
