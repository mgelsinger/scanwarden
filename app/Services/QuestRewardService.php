<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\SectorEnergy;
use App\Models\User;
use App\Models\UserEssence;
use Illuminate\Support\Facades\DB;

class QuestRewardService
{
    /**
     * Grant rewards to a user based on a quest's reward payload
     */
    public function grantRewards(User $user, Quest $quest): void
    {
        DB::transaction(function () use ($user, $quest) {
            $rewards = $quest->reward_payload;

            if (!is_array($rewards)) {
                return;
            }

            foreach ($rewards as $reward) {
                $this->grantSingleReward($user, $reward);
            }
        });
    }

    /**
     * Grant a single reward to a user
     */
    private function grantSingleReward(User $user, array $reward): void
    {
        $type = $reward['type'] ?? null;

        if ($type === 'essence') {
            $this->grantEssence($user, $reward);
        } elseif ($type === 'sector_energy') {
            $this->grantSectorEnergy($user, $reward);
        }
    }

    /**
     * Grant essence reward (generic or sector-specific)
     */
    private function grantEssence(User $user, array $reward): void
    {
        $essenceType = $reward['essence_type'] ?? null;
        $amount = $reward['amount'] ?? 0;

        if ($amount <= 0) {
            return;
        }

        if ($essenceType === 'generic') {
            // Grant generic essence
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
        } elseif ($essenceType === 'sector') {
            // Grant sector-specific essence
            $sectorId = $reward['sector_id'] ?? null;

            if (!$sectorId) {
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
    }

    /**
     * Grant sector energy reward
     */
    private function grantSectorEnergy(User $user, array $reward): void
    {
        $sectorId = $reward['sector_id'] ?? null;
        $amount = $reward['amount'] ?? 0;

        if (!$sectorId || $amount <= 0) {
            return;
        }

        $sectorEnergy = SectorEnergy::firstOrCreate(
            [
                'user_id' => $user->id,
                'sector_id' => $sectorId,
            ],
            [
                'current_energy' => 0,
                'max_energy' => 100,
            ]
        );

        $sectorEnergy->increment('current_energy', $amount);
    }
}
