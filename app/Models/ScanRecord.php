<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanRecord extends Model
{
    protected $fillable = [
        'user_id',
        'raw_upc',
        'sector_id',
        'rewards',
    ];

    protected function casts(): array
    {
        return [
            'rewards' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
