<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmutationRecipe extends Model
{
    protected $fillable = [
        'name',
        'description',
        'required_inputs',
        'outputs',
        'sector_id',
        'is_active',
        'level_requirement',
    ];

    protected $casts = [
        'required_inputs' => 'array',
        'outputs' => 'array',
        'is_active' => 'boolean',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
