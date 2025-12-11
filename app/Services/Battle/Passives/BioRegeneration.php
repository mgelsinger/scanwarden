<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * BioRegeneration - Regenerative Tissue
 *
 * Heals 10% max HP at the end of each of its turns.
 */
class BioRegeneration implements PassiveAbilityInterface
{
    public function getKey(): string
    {
        return 'bio_regeneration';
    }

    public function appliesTo(SummonedUnit $unit): bool
    {
        return true;
    }

    public function onBattleStart(array &$battleState, string $unitId): void
    {
        // No setup needed
    }

    public function beforeUnitActs(array &$battleState, string $unitId): void
    {
        // No action before unit acts
    }

    public function afterUnitActs(array &$battleState, string $unitId): void
    {
        // Find the unit in all_units array
        foreach ($battleState['all_units'] as &$unit) {
            if ($this->getUnitIdentifier($unit) === $unitId) {
                // Only heal if unit is still alive
                if ($unit['hp'] > 0) {
                    $maxHp = $unit['max_hp'];
                    $healAmount = (int) floor($maxHp * 0.10);

                    $oldHp = $unit['hp'];
                    $unit['hp'] = min($unit['hp'] + $healAmount, $maxHp);
                    $actualHeal = $unit['hp'] - $oldHp;

                    // Mark that healing occurred for logging
                    if ($actualHeal > 0) {
                        $unit['last_heal'] = $actualHeal;
                    }
                }
                break;
            }
        }

        // Sync changes back to team arrays
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
