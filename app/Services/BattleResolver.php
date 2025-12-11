<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * BattleResolver - Auto-resolves battles between two teams
 *
 * This service handles the turn-based combat system for ScanWarden.
 * It can work with real teams or synthetic AI teams.
 */
class BattleResolver
{
    private const MAX_TURNS = 50;

    /**
     * Resolve a battle between two teams
     *
     * @param Team|Collection $attackerTeam Team model or collection of unit data
     * @param Team|Collection $defenderTeam Team model or collection of unit data
     * @return array Battle results with winner, logs, and final state
     */
    public function resolveBattle($attackerTeam, $defenderTeam): array
    {
        // Convert teams to unit arrays
        $attackerUnits = $this->prepareUnits($attackerTeam, 'attacker');
        $defenderUnits = $this->prepareUnits($defenderTeam, 'defender');

        $allUnits = array_merge($attackerUnits, $defenderUnits);
        $turnLogs = [];
        $turnNumber = 0;

        while ($turnNumber < self::MAX_TURNS) {
            // Filter living units
            $livingUnits = array_values(array_filter($allUnits, fn($u) => $u['hp'] > 0));

            if (empty($livingUnits)) {
                break;
            }

            // Check if one team is eliminated
            $livingAttackers = array_filter($livingUnits, fn($u) => $u['team'] === 'attacker');
            $livingDefenders = array_filter($livingUnits, fn($u) => $u['team'] === 'defender');

            if (empty($livingAttackers) || empty($livingDefenders)) {
                break;
            }

            // Sort by speed (highest acts first)
            usort($livingUnits, fn($a, $b) => $b['speed'] <=> $a['speed']);

            // Current unit acts
            $currentUnit = $livingUnits[0];

            // Find target (lowest HP enemy for consistent behavior)
            $enemies = $currentUnit['team'] === 'attacker'
                ? array_values($livingDefenders)
                : array_values($livingAttackers);

            usort($enemies, fn($a, $b) => $a['hp'] <=> $b['hp']);
            $target = $enemies[0];

            // Calculate damage: attack - (defense * 0.5), minimum 1
            $rawDamage = $currentUnit['attack'] - (int) floor($target['defense'] * 0.5);
            $damage = max(1, $rawDamage);

            // Apply damage to all units array
            foreach ($allUnits as &$unit) {
                if ($unit['id'] === $target['id'] && $unit['team'] === $target['team']) {
                    $unit['hp'] = max($unit['hp'] - $damage, 0);
                    $wasKO = $unit['hp'] <= 0;

                    // Log this turn
                    $turnLogs[] = [
                        'turn' => $turnNumber + 1,
                        'attacker' => $currentUnit['name'],
                        'attacker_team' => $currentUnit['team'],
                        'defender' => $target['name'],
                        'defender_team' => $target['team'],
                        'damage' => $damage,
                        'defender_hp_after' => $unit['hp'],
                        'was_ko' => $wasKO,
                    ];
                    break;
                }
            }

            $turnNumber++;
        }

        // Determine winner
        $finalLivingUnits = array_filter($allUnits, fn($u) => $u['hp'] > 0);
        $finalAttackers = array_filter($finalLivingUnits, fn($u) => $u['team'] === 'attacker');
        $finalDefenders = array_filter($finalLivingUnits, fn($u) => $u['team'] === 'defender');

        $winner = $this->determineWinner($finalAttackers, $finalDefenders, $turnNumber);

        return [
            'winner' => $winner,
            'turns' => $turnLogs,
            'total_turns' => $turnNumber,
            'attacker_survivors' => count($finalAttackers),
            'defender_survivors' => count($finalDefenders),
        ];
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
        ];
    }

    /**
     * Determine battle winner
     *
     * @param array $finalAttackers
     * @param array $finalDefenders
     * @param int $turnNumber
     * @return string 'attacker', 'defender', or 'draw'
     */
    private function determineWinner(array $finalAttackers, array $finalDefenders, int $turnNumber): string
    {
        $attackerCount = count($finalAttackers);
        $defenderCount = count($finalDefenders);

        if ($attackerCount > 0 && $defenderCount === 0) {
            return 'attacker';
        }

        if ($defenderCount > 0 && $attackerCount === 0) {
            return 'defender';
        }

        // Draw or timeout - compare remaining HP
        if ($turnNumber >= self::MAX_TURNS) {
            $attackerTotalHp = array_sum(array_column($finalAttackers, 'hp'));
            $defenderTotalHp = array_sum(array_column($finalDefenders, 'hp'));

            if ($attackerTotalHp > $defenderTotalHp) {
                return 'attacker';
            } elseif ($defenderTotalHp > $attackerTotalHp) {
                return 'defender';
            }

            return 'draw';
        }

        return 'draw';
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
            $units[] = [
                'id' => 'dummy_' . $i,
                'name' => $names[$i],
                'hp' => $stats['hp'],
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'speed' => $stats['speed'] + rand(-2, 2), // Slight variation
            ];
        }

        return $units;
    }
}
