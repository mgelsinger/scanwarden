<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoreEntry extends Model
{
    protected $fillable = [
        'sector_id',
        'title',
        'body',
        'unlock_key',
        'unlock_threshold',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function unlockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_lore_entries')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }
}
