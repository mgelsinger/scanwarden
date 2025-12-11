<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\SummonedUnit;
use App\Services\Battle\BattleResult;
use App\Services\Battle\Passives\PassiveEngine;
use Illuminate\Support\Collection;

/**
 * BattleResolver - Auto-resolves battles between two teams
 *
 * This service handles the turn-based combat system for ScanWarden.
 * It can work with real teams or synthetic AI teams.
 *
 * Features:
 * - Deterministic battles with optional seed for testing
 * - Passive ability system integrated through hooks
 * - Returns structured BattleResult DTO
 */
class BattleResolver
{
    private const MAX_TURNS = 50;

    /** @var bool Enable damage variance */
    private bool $enableVariance = false;

    /** @var int|null RNG seed for deterministic battles */
    private ?int $seed = null;

    /** @var PassiveEngine Passive ability engine */
    private PassiveEngine $passiveEngine;

    public function __construct()
    {
        $this->passiveEngine = new PassiveEngine();
    }

    /**
     * Resolve a battle between two teams (new standardized API)
     *
     * @param User $attacker The attacking user
     * @param Team|Collection|array $attackerTeam Team model or collection of unit data
     * @param User|null $defender The defending user (null for practice/AI battles)
     * @param Team|Collection|array $defenderTeam Team model or collection of unit data
     * @param array $options Optional configuration: ['seed' => int, 'enable_variance' => bool, 'max_turns' => int]
     * @return BattleResult
     */
    public function resolve(
        User $attacker,
        $attackerTeam,
        ?User $defender,
        $defenderTeam,
        array $options = []
    ): BattleResult {
        // Configure options
        $this->seed = $options['seed'] ?? null;
        $this->enableVariance = $options['enable_variance'] ?? false;
        $maxTurns = $options['max_turns'] ?? self::MAX_TURNS;

        // Seed RNG if provided
        if ($this->seed !== null) {
            mt_srand($this->seed);
        }

        // Initialize battle state
        $battleState = $this->initializeBattleState($attackerTeam, $defenderTeam);

        // Attach passives and trigger onBattleStart
        $this->attachPassivesToBattle($battleState);

        // Run battle simulation
        $turnLogs = [];
        $turnNumber = 0;

        while ($turnNumber < $maxTurns) {
            // Check battle end condition
            if ($this->checkBattleEndCondition($battleState)) {
                break;
            }

            // Determine turn order
            $activeUnits = $this->determineTurnOrder($battleState);

            if (empty($activeUnits)) {
                break;
            }

            // Current unit acts
            $currentUnit = $activeUnits[0];
            $unitId = $this->getUnitIdentifier($currentUnit);

            // Before turn hook - trigger passives
            $this->beforeTurnHook($currentUnit, $battleState, $unitId);

            // Perform action
            $actionResult = $this->performAction($currentUnit, $battleState, $unitId);

            // After turn hook - trigger passives
            $this->afterTurnHook($currentUnit, $actionResult, $battleState, $unitId);

            // Log turn
            $turnLogs[] = $this->createTurnLog($turnNumber + 1, $currentUnit, $actionResult);

            $turnNumber++;
        }

        // Determine final outcome
        $outcome = $this->determineFinalOutcome($battleState, $turnNumber, $maxTurns);

        // Determine winner user ID
        $winnerUserId = $this->determineWinnerUserId($outcome, $attacker, $defender);

        // Get final unit states
        $finalStates = $this->getFinalStates($battleState);

        // Count survivors
        $attackerSurvivors = count(array_filter($battleState['attacker_units'], fn($u) => $u['hp'] > 0));
        $defenderSurvivors = count(array_filter($battleState['defender_units'], fn($u) => $u['hp'] > 0));

        return new BattleResult(
            outcome: $outcome,
            winnerUserId: $winnerUserId,
            turns: $turnLogs,
            finalStates: $finalStates,
            totalTurns: $turnNumber,
            attackerSurvivors: $attackerSurvivors,
            defenderSurvivors: $defenderSurvivors
        );
    }

    /**
     * Initialize battle state from teams
     *
     * @param mixed $attackerTeam
     * @param mixed $defenderTeam
     * @return array
     */
    private function initializeBattleState($attackerTeam, $defenderTeam): array
    {
        $attackerUnits = $this->prepareUnits($attackerTeam, 'attacker');
        $defenderUnits = $this->prepareUnits($defenderTeam, 'defender');

        return [
            'attacker_units' => $attackerUnits,
            'defender_units' => $defenderUnits,
            'all_units' => array_merge($attackerUnits, $defenderUnits),
        ];
    }

    /**
     * Attach passives to battle and trigger onBattleStart
     *
     * @param array $battleState
     * @return void
     */
    private function attachPassivesToBattle(array &$battleState): void
    {
        // Build map of unit ID => SummonedUnit model
        $unitsById = [];

        foreach ($battleState['all_units'] as $unit) {
            $unitId = $this->getUnitIdentifier($unit);

            // Only attach passives if unit has a model (not AI dummy)
            if (isset($unit['model']) && $unit['model'] instanceof SummonedUnit) {
                $unitsById[$unitId] = $unit['model'];
            }
        }

        // Attach passives via engine
        $this->passiveEngine->attachPassivesToBattleState($battleState, $unitsById);

        // Trigger onBattleStart for all passives
        $this->passiveEngine->triggerOnBattleStart($battleState);
    }

    /**
     * Get unique identifier for a unit
     *
     * @param array $unit
     * @return string
     */
    private function getUnitIdentifier(array $unit): string
    {
        return $unit['team'] . '_' . $unit['id'];
    }

    /**
     * Determine turn order (fastest units first)
     *
     * @param array $battleState
     * @return array Living units sorted by speed
     */
    private function determineTurnOrder(array &$battleState): array
    {
        $livingUnits = array_values(array_filter($battleState['all_units'], fn($u) => $u['hp'] > 0));

        // Sort by speed (highest acts first)
        usort($livingUnits, fn($a, $b) => $b['speed'] <=> $a['speed']);

        return $livingUnits;
    }

    /**
     * Hook called before each turn - triggers passives
     *
     * @param array $currentUnit
     * @param array $battleState
     * @param string $unitId
     * @return void
     */
    private function beforeTurnHook(array $currentUnit, array &$battleState, string $unitId): void
    {
        // Trigger passive hooks for this unit
        $this->passiveEngine->triggerBeforeUnitActs($battleState, $unitId);
    }

    /**
     * Perform unit's action (attack)
     *
     * @param array $currentUnit
     * @param array $battleState
     * @param string $unitId
     * @return array Action result with target and damage info
     */
    private function performAction(array $currentUnit, array &$battleState, string $unitId): array
    {
        // Find target (lowest HP enemy)
        $enemies = $this->getEnemies($currentUnit['team'], $battleState);

        if (empty($enemies)) {
            return [
                'success' => false,
                'reason' => 'no_targets',
            ];
        }

        usort($enemies, fn($a, $b) => $a['hp'] <=> $b['hp']);
        $target = $enemies[0];
        $targetId = $this->getUnitIdentifier($target);

        // Calculate base damage: attack - (defense * 0.5), minimum 1
        $rawDamage = $currentUnit['attack'] - (int) floor($target['defense'] * 0.5);
        $damage = max(1, $rawDamage);

        // Apply attacker's damage_out_multiplier (from passives)
        $damageOutMultiplier = $battleState['units'][$unitId]['stats']['damage_out_multiplier'] ?? 1.0;
        $damage = (int) floor($damage * $damageOutMultiplier);

        // Apply defender's damage_in_multiplier (from passives)
        $damageInMultiplier = $battleState['units'][$targetId]['stats']['damage_in_multiplier'] ?? 1.0;
        $damage = (int) floor($damage * $damageInMultiplier);

        // Ensure minimum damage of 1
        $damage = max(1, $damage);

        // Apply variance if enabled
        if ($this->enableVariance) {
            $variance = $this->randomBetween(-2, 2);
            $damage = max(1, $damage + $variance);
        }

        // Apply damage to target
        $wasKO = false;
        foreach ($battleState['all_units'] as &$unit) {
            if ($unit['id'] === $target['id'] && $unit['team'] === $target['team']) {
                $unit['hp'] = max($unit['hp'] - $damage, 0);
                $wasKO = $unit['hp'] <= 0;
                break;
            }
        }

        // Update team-specific arrays
        $this->syncTeamUnits($battleState);

        return [
            'success' => true,
            'attacker' => $currentUnit,
            'target' => $target,
            'damage' => $damage,
            'target_hp_after' => $target['hp'] - $damage < 0 ? 0 : $target['hp'] - $damage,
            'was_ko' => $wasKO,
        ];
    }

    /**
     * Hook called after each turn - triggers passives
     *
     * @param array $currentUnit
     * @param array $actionResult
     * @param array $battleState
     * @param string $unitId
     * @return void
     */
    private function afterTurnHook(array $currentUnit, array $actionResult, array &$battleState, string $unitId): void
    {
        // Trigger passive hooks for this unit
        $this->passiveEngine->triggerAfterUnitActs($battleState, $unitId);
    }

    /**
     * Check if battle should end
     *
     * @param array $battleState
     * @return bool
     */
    private function checkBattleEndCondition(array $battleState): bool
    {
        $livingAttackers = array_filter($battleState['attacker_units'], fn($u) => $u['hp'] > 0);
        $livingDefenders = array_filter($battleState['defender_units'], fn($u) => $u['hp'] > 0);

        return empty($livingAttackers) || empty($livingDefenders);
    }

    /**
     * Determine final battle outcome
     *
     * @param array $battleState
     * @param int $turnNumber
     * @param int $maxTurns
     * @return string 'attacker_win', 'defender_win', or 'draw'
     */
    private function determineFinalOutcome(array $battleState, int $turnNumber, int $maxTurns): string
    {
        $livingAttackers = array_filter($battleState['attacker_units'], fn($u) => $u['hp'] > 0);
        $livingDefenders = array_filter($battleState['defender_units'], fn($u) => $u['hp'] > 0);

        $attackerCount = count($livingAttackers);
        $defenderCount = count($livingDefenders);

        if ($attackerCount > 0 && $defenderCount === 0) {
            return 'attacker_win';
        }

        if ($defenderCount > 0 && $attackerCount === 0) {
            return 'defender_win';
        }

        // Draw or timeout - compare remaining HP
        if ($turnNumber >= $maxTurns) {
            $attackerTotalHp = array_sum(array_column($livingAttackers, 'hp'));
            $defenderTotalHp = array_sum(array_column($livingDefenders, 'hp'));

            if ($attackerTotalHp > $defenderTotalHp) {
                return 'attacker_win';
            } elseif ($defenderTotalHp > $attackerTotalHp) {
                return 'defender_win';
            }
        }

        return 'draw';
    }

    /**
     * Determine winner user ID from outcome
     *
     * @param string $outcome
     * @param User $attacker
     * @param User|null $defender
     * @return int|null
     */
    private function determineWinnerUserId(string $outcome, User $attacker, ?User $defender): ?int
    {
        return match ($outcome) {
            'attacker_win' => $attacker->id,
            'defender_win' => $defender?->id,
            default => null,
        };
    }

    /**
     * Get enemies for a given team
     *
     * @param string $team
     * @param array $battleState
     * @return array
     */
    private function getEnemies(string $team, array $battleState): array
    {
        $enemyUnits = $team === 'attacker'
            ? $battleState['defender_units']
            : $battleState['attacker_units'];

        return array_values(array_filter($enemyUnits, fn($u) => $u['hp'] > 0));
    }

    /**
     * Sync all_units changes back to team-specific arrays
     *
     * @param array $battleState
     * @return void
     */
    private function syncTeamUnits(array &$battleState): void
    {
        $battleState['attacker_units'] = array_values(array_filter(
            $battleState['all_units'],
            fn($u) => $u['team'] === 'attacker'
        ));

        $battleState['defender_units'] = array_values(array_filter(
            $battleState['all_units'],
            fn($u) => $u['team'] === 'defender'
        ));
    }

    /**
     * Create a turn log entry
     *
     * @param int $turnNumber
     * @param array $currentUnit
     * @param array $actionResult
     * @return array
     */
    private function createTurnLog(int $turnNumber, array $currentUnit, array $actionResult): array
    {
        if (!$actionResult['success']) {
            return [
                'turn' => $turnNumber,
                'success' => false,
                'reason' => $actionResult['reason'] ?? 'unknown',
            ];
        }

        return [
            'turn' => $turnNumber,
            'attacker' => $currentUnit['name'],
            'attacker_team' => $currentUnit['team'],
            'defender' => $actionResult['target']['name'],
            'defender_team' => $actionResult['target']['team'],
            'damage' => $actionResult['damage'],
            'defender_hp_after' => $actionResult['target_hp_after'],
            'was_ko' => $actionResult['was_ko'],
        ];
    }

    /**
     * Get final states of all units
     *
     * @param array $battleState
     * @return array
     */
    private function getFinalStates(array $battleState): array
    {
        return $battleState['all_units'];
    }

    /**
     * Random number between min and max (controllable for testing)
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    private function randomBetween(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Prepare units from a team or collection
     *
     * @param Team|Collection|array $source
     * @param string $teamSide
     * @return array
     */
    private function prepareUnits($source, string $teamSide): array
    {
        if ($source instanceof Team) {
            return $source->units()->get()->map(function ($unit) use ($teamSide) {
                return $this->unitToArray($unit, $teamSide);
            })->toArray();
        }

        if ($source instanceof Collection) {
            return $source->map(function ($unit) use ($teamSide) {
                return $this->unitToArray($unit, $teamSide);
            })->toArray();
        }

        // Assume it's already an array of unit data
        return array_map(function ($unit) use ($teamSide) {
            return array_merge($unit, ['team' => $teamSide]);
        }, $source);
    }

    /**
     * Convert a unit model/object to battle array format
     *
     * @param mixed $unit
     * @param string $teamSide
     * @return array
     */
    private function unitToArray($unit, string $teamSide): array
    {
        return [
            'id' => $unit->id ?? $unit['id'] ?? uniqid(),
            'name' => $unit->name ?? $unit['name'],
            'hp' => $unit->hp ?? $unit['hp'],
            'max_hp' => $unit->hp ?? $unit['hp'],
            'attack' => $unit->attack ?? $unit['attack'],
            'defense' => $unit->defense ?? $unit['defense'],
            'speed' => $unit->speed ?? $unit['speed'],
            'team' => $teamSide,
            'model' => ($unit instanceof SummonedUnit) ? $unit : null, // Store reference for passives
        ];
    }

    /**
     * Generate a dummy AI team for practice battles
     *
     * @param int $unitCount Number of units (1-5)
     * @param string $difficulty 'easy', 'medium', or 'hard'
     * @return array Array of unit data
     */
    public function generateDummyTeam(int $unitCount = 3, string $difficulty = 'medium'): array
    {
        $unitCount = max(1, min(5, $unitCount));

        $stats = match($difficulty) {
            'easy' => ['hp' => 50, 'attack' => 10, 'defense' => 5, 'speed' => 10],
            'hard' => ['hp' => 120, 'attack' => 30, 'defense' => 20, 'speed' => 25],
            default => ['hp' => 80, 'attack' => 20, 'defense' => 12, 'speed' => 15],
        };

        $names = [
            'Training Dummy Alpha',
            'Training Dummy Beta',
            'Training Dummy Gamma',
            'Training Dummy Delta',
            'Training Dummy Epsilon',
        ];

        $units = [];
        for ($i = 0; $i < $unitCount; $i++) {
            // Use deterministic speed if seed is set
            $speedVariation = $this->seed !== null ? 0 : $this->randomBetween(-2, 2);

            $units[] = [
                'id' => 'dummy_' . $i,
                'name' => $names[$i],
                'hp' => $stats['hp'],
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'speed' => $stats['speed'] + $speedVariation,
            ];
        }

        return $units;
    }

    /**
     * Legacy method for backwards compatibility
     *
     * @deprecated Use resolve() instead
     * @param Team|Collection $attackerTeam
     * @param Team|Collection $defenderTeam
     * @return array
     */
    public function resolveBattle($attackerTeam, $defenderTeam): array
    {
        // Create dummy users for legacy API
        $dummyAttacker = new User(['id' => 0]);
        $dummyDefender = new User(['id' => 0]);

        $result = $this->resolve($dummyAttacker, $attackerTeam, $dummyDefender, $defenderTeam);

        // Convert to legacy format
        return [
            'winner' => $result->getWinnerSide(),
            'turns' => $result->turns,
            'total_turns' => $result->totalTurns,
            'attacker_survivors' => $result->attackerSurvivors,
            'defender_survivors' => $result->defenderSurvivors,
        ];
    }
}
