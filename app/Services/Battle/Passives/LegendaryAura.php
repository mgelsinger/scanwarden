<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * LegendaryAura - Mythic Presence
 *
 * Deals +10% damage and takes 10% less damage.
 * This passive is automatically applied to all legendary rarity units.
 */
class LegendaryAura implements PassiveAbilityInterface
{
    private const DAMAGE_OUT_MULTIPLIER = 1.10;
    private const DAMAGE_IN_MULTIPLIER = 0.90;

    public function getKey(): string
    {
        return 'legendary_aura';
    }

    public function appliesTo(SummonedUnit $unit): bool
    {
        return $unit->rarity === 'legendary';
    }

    public function onBattleStart(array &$battleState, string $unitId): void
    {
        // Set permanent damage multipliers for this unit
        if (!isset($battleState['units'][$unitId]['stats'])) {
            $battleState['units'][$unitId]['stats'] = [];
        }

        // Stack with existing multipliers
        $battleState['units'][$unitId]['stats']['damage_out_multiplier'] =
            ($battleState['units'][$unitId]['stats']['damage_out_multiplier'] ?? 1.0) * self::DAMAGE_OUT_MULTIPLIER;

        $battleState['units'][$unitId]['stats']['damage_in_multiplier'] =
            ($battleState['units'][$unitId]['stats']['damage_in_multiplier'] ?? 1.0) * self::DAMAGE_IN_MULTIPLIER;
    }

    public function beforeUnitActs(array &$battleState, string $unitId): void
    {
        // No action needed - multipliers are permanent
    }

    public function afterUnitActs(array &$battleState, string $unitId): void
    {
        // No action needed - multipliers are permanent
    }
}
