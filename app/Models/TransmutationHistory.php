<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmutationHistory extends Model
{
    protected $table = 'transmutation_history';

    protected $fillable = [
        'user_id',
        'recipe_id',
        'inputs_consumed',
        'outputs_received',
    ];

    protected $casts = [
        'inputs_consumed' => 'array',
        'outputs_received' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(TransmutationRecipe::class);
    }
}
