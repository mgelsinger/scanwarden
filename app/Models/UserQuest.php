<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuest extends Model
{
    protected $fillable = [
        'user_id',
        'quest_id',
        'progress',
        'target_value',
        'is_completed',
        'completed_at',
        'assigned_at',
        'expires_at',
        'last_progress_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_progress_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}
