<?php

namespace App\Services;

use App\Exceptions\InsufficientResourcesException;
use App\Models\SectorEnergy;
use App\Models\User;
use App\Models\UserEssence;

class ResourceService
{
    /**
     * Grant generic essence to a user
     */
    public function grantGenericEssence(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $essence = UserEssence::firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'generic',
                'sector_id' => null,
            ],
            [
                'amount' => 0,
            ]
        );

        $essence->increment('amount', $amount);
    }

    /**
     * Grant sector-specific essence to a user
     */
    public function grantSectorEssence(User $user, int $sectorId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $essence = UserEssence::firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'sector',
                'sector_id' => $sectorId,
            ],
            [
                'amount' => 0,
            ]
        );

        $essence->increment('amount', $amount);
    }

    /**
     * Grant sector energy to a user
     */
    public function grantSectorEnergy(User $user, int $sectorId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $energy = SectorEnergy::firstOrCreate(
            [
                'user_id' => $user->id,
                'sector_id' => $sectorId,
            ],
            [
                'current_energy' => 0,
            ]
        );

        $energy->increment('current_energy', $amount);
    }

    /**
     * Deduct generic essence from a user
     *
     * @throws InsufficientResourcesException if user doesn't have enough
     */
    public function deductGenericEssence(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        if (!$this->userHasGenericEssence($user, $amount)) {
            throw new InsufficientResourcesException("User does not have {$amount} generic essence");
        }

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->firstOrFail();

        $essence->decrement('amount', $amount);
    }

    /**
     * Deduct sector-specific essence from a user
     *
     * @throws InsufficientResourcesException if user doesn't have enough
     */
    public function deductSectorEssence(User $user, int $sectorId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        if (!$this->userHasSectorEssence($user, $sectorId, $amount)) {
            throw new InsufficientResourcesException("User does not have {$amount} essence for sector {$sectorId}");
        }

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sectorId)
            ->firstOrFail();

        $essence->decrement('amount', $amount);
    }

    /**
     * Deduct sector energy from a user
     *
     * @throws InsufficientResourcesException if user doesn't have enough
     */
    public function deductSectorEnergy(User $user, int $sectorId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        if (!$this->userHasSectorEnergy($user, $sectorId, $amount)) {
            throw new InsufficientResourcesException("User does not have {$amount} energy for sector {$sectorId}");
        }

        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sectorId)
            ->firstOrFail();

        $energy->decrement('current_energy', $amount);
    }

    /**
     * Check if user has enough generic essence
     */
    public function userHasGenericEssence(User $user, int $amount): bool
    {
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        return $essence && $essence->amount >= $amount;
    }

    /**
     * Check if user has enough sector-specific essence
     */
    public function userHasSectorEssence(User $user, int $sectorId, int $amount): bool
    {
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sectorId)
            ->first();

        return $essence && $essence->amount >= $amount;
    }

    /**
     * Check if user has enough sector energy
     */
    public function userHasSectorEnergy(User $user, int $sectorId, int $amount): bool
    {
        $energy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sectorId)
            ->first();

        return $energy && $energy->current_energy >= $amount;
    }
}
