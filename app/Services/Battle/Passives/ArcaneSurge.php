<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * ArcaneSurge - Arcane Surge
 *
 * Gains +5 Speed for the first 3 turns.
 */
class ArcaneSurge implements PassiveAbilityInterface
{
    private const SPEED_BONUS = 5;
    private const DURATION_TURNS = 3;

    public function getKey(): string
    {
        return 'arcane_surge';
    }

    public function appliesTo(SummonedUnit $unit): bool
    {
        // This passive applies to all non-common units
        return $unit->rarity !== 'common';
    }

    public function onBattleStart(array &$battleState, string $unitId): void
    {
        // Initialize surge state
        if (!isset($battleState['passive_state'])) {
            $battleState['passive_state'] = [];
        }

        $battleState['passive_state'][$unitId]['arcane_surge_turns_remaining'] = self::DURATION_TURNS;

        // Apply initial speed boost
        $this->applySpeedBoost($battleState, $unitId, true);
    }

    public function beforeUnitActs(array &$battleState, string $unitId): void
    {
        // No action needed before acting
    }

    public function afterUnitActs(array &$battleState, string $unitId): void
    {
        // Decrement turns remaining
        $turnsRemaining = $battleState['passive_state'][$unitId]['arcane_surge_turns_remaining'] ?? 0;

        if ($turnsRemaining > 0) {
            $turnsRemaining--;
            $battleState['passive_state'][$unitId]['arcane_surge_turns_remaining'] = $turnsRemaining;

            // Remove speed boost if duration expired
            if ($turnsRemaining === 0) {
                $this->removeSpeedBoost($battleState, $unitId);
            }
        }
    }

    /**
     * Apply speed boost to unit
     */
    private function applySpeedBoost(array &$battleState, string $unitId, bool $apply): void
    {
        // Find and modify the unit in all_units array
        foreach ($battleState['all_units'] as &$unit) {
            if ($this->getUnitIdentifier($unit) === $unitId) {
                if ($apply) {
                    // Store original speed if not already stored
                    if (!isset($unit['original_speed'])) {
                        $unit['original_speed'] = $unit['speed'];
                    }
                    $unit['speed'] = $unit['original_speed'] + self::SPEED_BONUS;
                }
                break;
            }
        }

        $this->syncTeamUnits($battleState);
    }

    /**
     * Remove speed boost from unit
     */
    private function removeSpeedBoost(array &$battleState, string $unitId): void
    {
        foreach ($battleState['all_units'] as &$unit) {
            if ($this->getUnitIdentifier($unit) === $unitId) {
                if (isset($unit['original_speed'])) {
                    $unit['speed'] = $unit['original_speed'];
                }
                break;
            }
        }

        $this->syncTeamUnits($battleState);
    }

    /**
     * Get unique identifier for a unit
     */
    private function getUnitIdentifier(array $unit): string
    {
        return $unit['team'] . '_' . $unit['id'];
    }

    /**
     * Sync all_units changes back to team-specific arrays
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
}
