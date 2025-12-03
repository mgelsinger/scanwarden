<?php

namespace App\Services;

use App\Models\Team;

class BattleSimulatorService
{
    public function simulateBattle(Team $attackerTeam, Team $defenderTeam): array
    {
        // Load units with stats
        $attackerUnits = $attackerTeam->units()->get()->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'hp' => $unit->hp,
                'max_hp' => $unit->hp,
                'attack' => $unit->attack,
                'defense' => $unit->defense,
                'speed' => $unit->speed,
                'team' => 'attacker',
            ];
        })->toArray();

        $defenderUnits = $defenderTeam->units()->get()->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'hp' => $unit->hp,
                'max_hp' => $unit->hp,
                'attack' => $unit->attack,
                'defense' => $unit->defense,
                'speed' => $unit->speed,
                'team' => 'defender',
            ];
        })->toArray();

        $allUnits = array_merge($attackerUnits, $defenderUnits);
        $turnLogs = [];
        $turnIndex = 0;
        $maxTurns = 100; // Prevent infinite loops

        while ($turnIndex < $maxTurns) {
            // Filter living units
            $livingUnits = array_filter($allUnits, fn($u) => $u['hp'] > 0);

            if (empty($livingUnits)) {
                break;
            }

            // Check if one team is eliminated
            $livingAttackers = array_filter($livingUnits, fn($u) => $u['team'] === 'attacker');
            $livingDefenders = array_filter($livingUnits, fn($u) => $u['team'] === 'defender');

            if (empty($livingAttackers) || empty($livingDefenders)) {
                break;
            }

            // Sort by speed (highest first)
            usort($livingUnits, fn($a, $b) => $b['speed'] <=> $a['speed']);

            // Current attacker
            $currentUnit = $livingUnits[0];

            // Find target (random enemy)
            $enemies = $currentUnit['team'] === 'attacker' ? $livingDefenders : $livingAttackers;
            $target = $enemies[array_rand($enemies)];

            // Calculate damage
            $damage = max($currentUnit['attack'] - $target['defense'], 1);

            // Apply damage
            foreach ($allUnits as &$unit) {
                if ($unit['id'] === $target['id'] && $unit['team'] === $target['team']) {
                    $unit['hp'] = max($unit['hp'] - $damage, 0);
                    break;
                }
            }

            // Log turn
            $turnLogs[] = [
                'turn' => $turnIndex + 1,
                'attacker' => $currentUnit['name'],
                'attacker_team' => $currentUnit['team'],
                'defender' => $target['name'],
                'defender_team' => $target['team'],
                'damage' => $damage,
                'defender_hp_after' => max($target['hp'] - $damage, 0),
            ];

            $turnIndex++;
        }

        // Determine winner
        $finalLivingUnits = array_filter($allUnits, fn($u) => $u['hp'] > 0);
        $finalAttackers = array_filter($finalLivingUnits, fn($u) => $u['team'] === 'attacker');
        $finalDefenders = array_filter($finalLivingUnits, fn($u) => $u['team'] === 'defender');

        $winner = null;
        if (count($finalAttackers) > 0 && count($finalDefenders) === 0) {
            $winner = 'attacker';
        } elseif (count($finalDefenders) > 0 && count($finalAttackers) === 0) {
            $winner = 'defender';
        } elseif ($turnIndex >= $maxTurns) {
            // Timeout - determine by remaining HP
            $attackerTotalHp = array_sum(array_column($finalAttackers, 'hp'));
            $defenderTotalHp = array_sum(array_column($finalDefenders, 'hp'));
            $winner = $attackerTotalHp >= $defenderTotalHp ? 'attacker' : 'defender';
        }

        return [
            'winner' => $winner,
            'turns' => $turnLogs,
            'total_turns' => $turnIndex,
        ];
    }
}
