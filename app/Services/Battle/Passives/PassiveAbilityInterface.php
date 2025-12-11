<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * PassiveAbilityInterface
 *
 * Interface for all passive ability implementations.
 * Each passive plugs into the battle resolver through hooks at different phases.
 */
interface PassiveAbilityInterface
{
    /**
     * Get the unique key for this passive
     *
     * @return string Passive key (e.g., 'tech_overclock')
     */
    public function getKey(): string;

    /**
     * Should this passive be attached to this unit instance?
     *
     * @param SummonedUnit $unit The unit to check
     * @return bool True if this passive applies to the unit
     */
    public function appliesTo(SummonedUnit $unit): bool;

    /**
     * Hook called at battle start before any turns
     *
     * @param array $battleState Reference to the battle state array
     * @param string $unitId Internal unit ID in battle (e.g., 'attacker_0', 'defender_1')
     * @return void
     */
    public function onBattleStart(array &$battleState, string $unitId): void;

    /**
     * Hook called before the unit takes its action on its turn
     *
     * @param array $battleState Reference to the battle state array
     * @param string $unitId Internal unit ID in battle
     * @return void
     */
    public function beforeUnitActs(array &$battleState, string $unitId): void;

    /**
     * Hook called after unit has performed its action
     *
     * @param array $battleState Reference to the battle state array
     * @param string $unitId Internal unit ID in battle
     * @return void
     */
    public function afterUnitActs(array &$battleState, string $unitId): void;
}
