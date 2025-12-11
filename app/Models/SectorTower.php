<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectorTower extends Model
{
    protected $fillable = [
        'sector_id',
        'slug',
        'name',
        'description',
        'max_floor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_floor' => 'integer',
    ];

    /**
     * Get the sector that owns the tower.
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Get the stages for the tower.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(SectorTowerStage::class, 'tower_id');
    }

    /**
     * Get the user progress records for the tower.
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserTowerProgress::class, 'tower_id');
    }
}
