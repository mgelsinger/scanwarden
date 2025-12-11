<?php

namespace App\Services;

use App\Models\Sector;
use App\Models\SectorTower;
use App\Models\SectorTowerStage;
use App\Models\User;
use App\Models\UserTowerProgress;
use App\Services\Battle\BattleResult;
use Illuminate\Support\Facades\DB;

class SectorTowerService
{
    public function __construct(
        private ResourceService $resourceService
    ) {
    }

    /**
     * Get or create user progress for a tower.
     */
    public function getOrCreateProgress(User $user, SectorTower $tower): UserTowerProgress
    {
        return UserTowerProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'tower_id' => $tower->id,
            ],
            [
                'highest_floor_cleared' => 0,
            ]
        );
    }

    /**
     * Check if a user can attempt a specific floor.
     */
    public function canAttemptFloor(UserTowerProgress $progress, SectorTowerStage $stage): bool
    {
        // Check if stage and tower are active
        if (!$stage->is_active || !$stage->tower->is_active) {
            return false;
        }

        // Floor 1 is always attemptable
        if ($stage->floor === 1) {
            return true;
        }

        // For other floors, require previous floor to be cleared
        return $progress->highest_floor_cleared >= ($stage->floor - 1);
    }

    /**
     * Build an AI enemy team from a stage definition.
     *
     * Returns an array of unit data structures compatible with BattleResolver.
     */
    public function buildEnemyTeamFromStage(SectorTowerStage $stage): array
    {
        $enemyTeam = [];

        foreach ($stage->enemy_team as $enemyDef) {
            // Load the sector for this enemy
            $sector = Sector::find($enemyDef['sector_id']);

            // Build a unit structure similar to what BattleResolver expects
            $enemyUnit = [
                'id' => $enemyDef['slot'], // Use slot as ID for enemy units
                'name' => $this->generateEnemyName($sector, $enemyDef['rarity'], $enemyDef['tier']),
                'hp' => $enemyDef['base_hp'],
                'max_hp' => $enemyDef['base_hp'],
                'attack' => $enemyDef['base_attack'],
                'defense' => $enemyDef['base_defense'],
                'speed' => $enemyDef['base_speed'],
                'rarity' => $enemyDef['rarity'],
                'tier' => $enemyDef['tier'] ?? 1,
                'sector' => $sector,
                'sector_id' => $sector->id,
                'passive_key' => $enemyDef['passive_key'] ?? null,
            ];

            $enemyTeam[] = $enemyUnit;
        }

        return $enemyTeam;
    }

    /**
     * Generate a name for an enemy unit based on its properties.
     */
    private function generateEnemyName(Sector $sector, string $rarity, int $tier): string
    {
        $rarityPrefix = match ($rarity) {
            'common' => '',
            'uncommon' => 'Enhanced ',
            'rare' => 'Elite ',
            'epic' => 'Champion ',
            'legendary' => 'Legendary ',
            default => '',
        };

        return $rarityPrefix . $sector->name . ' Guardian T' . $tier;
    }

    /**
     * Handle the result of a tower battle and update progress/rewards.
     *
     * @return array SectorTowerResult with battle outcome details
     */
    public function handleBattleResult(
        User $user,
        SectorTower $tower,
        SectorTowerStage $stage,
        BattleResult $result
    ): array {
        $progress = $this->getOrCreateProgress($user, $tower);

        $didWin = $result->getWinnerSide() === 'attacker';
        $isFirstClear = false;
        $rewardsGranted = [];
        $newHighestFloor = $progress->highest_floor_cleared;

        if ($didWin && $stage->floor > $progress->highest_floor_cleared) {
            // First clear - update progress and grant rewards
            $isFirstClear = true;
            $newHighestFloor = $stage->floor;

            // Update progress
            $progress->highest_floor_cleared = $newHighestFloor;

            // Grant rewards if any
            if ($stage->rewards && is_array($stage->rewards)) {
                $rewardsGranted = $this->resourceService->grantResources($user, $stage->rewards);
            }
        }

        // Always update last attempt timestamp
        $progress->last_attempt_at = now();
        $progress->save();

        return [
            'did_win' => $didWin,
            'first_clear' => $isFirstClear,
            'rewards_granted' => $rewardsGranted,
            'new_highest_floor_cleared' => $newHighestFloor,
        ];
    }
}
