<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleLog extends Model
{
    protected $fillable = [
        'battle_match_id',
        'turn_index',
        'turn_data',
    ];

    protected function casts(): array
    {
        return [
            'turn_data' => 'array',
        ];
    }

    public function battleMatch(): BelongsTo
    {
        return $this->belongsTo(BattleMatch::class);
    }
}
