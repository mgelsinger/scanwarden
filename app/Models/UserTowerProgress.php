<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTowerProgress extends Model
{
    protected $table = 'user_tower_progress';

    protected $fillable = [
        'user_id',
        'tower_id',
        'highest_floor_cleared',
        'last_attempt_at',
    ];

    protected $casts = [
        'highest_floor_cleared' => 'integer',
        'last_attempt_at' => 'datetime',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tower that this progress is for.
     */
    public function tower(): BelongsTo
    {
        return $this->belongsTo(SectorTower::class, 'tower_id');
    }
}
