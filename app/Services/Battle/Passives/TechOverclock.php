<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * TechOverclock - Overclocked Systems
 *
 * First attack deals +20% damage.
 */
class TechOverclock implements PassiveAbilityInterface
{
    public function getKey(): string
    {
        return 'tech_overclock';
    }

    public function appliesTo(SummonedUnit $unit): bool
    {
        // This passive is assigned by passive_key or default for Tech sector
        return true;
    }

    public function onBattleStart(array &$battleState, string $unitId): void
    {
        // Initialize flag to track if first attack has been made
        if (!isset($battleState['passive_state'])) {
            $battleState['passive_state'] = [];
        }

        $battleState['passive_state'][$unitId]['tech_overclock_first_attack'] = true;
    }

    public function beforeUnitActs(array &$battleState, string $unitId): void
    {
        // Check if this is the first attack
        $isFirstAttack = $battleState['passive_state'][$unitId]['tech_overclock_first_attack'] ?? false;

        if ($isFirstAttack) {
            // Set damage multiplier for this attack
            if (!isset($battleState['units'][$unitId]['stats'])) {
                $battleState['units'][$unitId]['stats'] = [];
            }

            $battleState['units'][$unitId]['stats']['damage_out_multiplier'] =
                ($battleState['units'][$unitId]['stats']['damage_out_multiplier'] ?? 1.0) * 1.20;

            // Mark that first attack has been used
            $battleState['passive_state'][$unitId]['tech_overclock_first_attack'] = false;
        }
    }

    public function afterUnitActs(array &$battleState, string $unitId): void
    {
        // Reset damage multiplier after the attack
        $isFirstAttack = $battleState['passive_state'][$unitId]['tech_overclock_first_attack'] ?? false;

        if (!$isFirstAttack && isset($battleState['units'][$unitId]['stats']['damage_out_multiplier'])) {
            // Remove the overclock bonus after first attack
            $currentMultiplier = $battleState['units'][$unitId]['stats']['damage_out_multiplier'];
            if ($currentMultiplier >= 1.19) { // Account for floating point
                $battleState['units'][$unitId]['stats']['damage_out_multiplier'] = $currentMultiplier / 1.20;
            }
        }
    }
}
