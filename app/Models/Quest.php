<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'category',
        'target_value',
        'is_daily',
        'is_repeatable',
        'is_active',
        'reward_payload',
        'reset_period',
    ];

    protected $casts = [
        'is_daily' => 'boolean',
        'is_repeatable' => 'boolean',
        'is_active' => 'boolean',
        'reward_payload' => 'array',
    ];

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }
}
