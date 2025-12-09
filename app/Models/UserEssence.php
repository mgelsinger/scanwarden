<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEssence extends Model
{
    protected $table = 'user_essence';

    protected $fillable = [
        'user_id',
        'sector_id',
        'amount',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Add essence to a user's balance.
     * Will increment existing essence or create a new record.
     *
     * @param int $userId The user ID
     * @param int $amount The amount of essence to add
     * @param string $type The type of essence ('generic' or 'sector')
     * @param int|null $sectorId The sector ID (required if type is 'sector')
     * @return UserEssence
     */
    public static function addEssence(int $userId, int $amount, string $type, ?int $sectorId = null): UserEssence
    {
        $existing = static::where('user_id', $userId)
            ->where('type', $type)
            ->where('sector_id', $sectorId)
            ->first();

        if ($existing) {
            $existing->increment('amount', $amount);
            return $existing->fresh();
        }

        return static::create([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'sector_id' => $sectorId,
        ]);
    }
}
