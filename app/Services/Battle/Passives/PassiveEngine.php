<?php

namespace App\Services\Battle\Passives;

use App\Models\SummonedUnit;

/**
 * PassiveEngine
 *
 * Manages passive abilities in battle. Acts as bridge between BattleResolver and passive implementations.
 * Resolves which passives apply to which units and triggers them at appropriate times.
 */
class PassiveEngine
{
    /** @var array<string, PassiveAbilityInterface> Available passive implementations */
    private array $passives = [];

    public function __construct()
    {
        // Register all available passives
        $this->passives = [
            'tech_overclock' => new TechOverclock(),
            'bio_regeneration' => new BioRegeneration(),
            'arcane_surge' => new ArcaneSurge(),
            'legendary_aura' => new LegendaryAura(),
        ];
    }

    /**
     * Attach passives to battle state for all units
     *
     * @param array $battleState Reference to battle state
     * @param array $unitsById Map of unit ID => SummonedUnit model
     * @return void
     */
    public function attachPassivesToBattleState(array &$battleState, array $unitsById): void
    {
        if (!isset($battleState['units'])) {
            $battleState['units'] = [];
        }

        foreach ($unitsById as $unitId => $unit) {
            $applicablePassives = $this->resolvePassivesForUnit($unit);

            $battleState['units'][$unitId]['passives'] = $applicablePassives;
            $battleState['units'][$unitId]['unit_model'] = $unit;
        }
    }

    /**
     * Resolve which passives apply to a given unit
     *
     * @param SummonedUnit $unit The unit to check
     * @return array<string, PassiveAbilityInterface> Applicable passives
     */
    public function resolvePassivesForUnit(SummonedUnit $unit): array
    {
        $applicablePassives = [];

        // If passive_key is explicitly set, use that
        if ($unit->passive_key && isset($this->passives[$unit->passive_key])) {
            $passive = $this->passives[$unit->passive_key];
            if ($passive->appliesTo($unit)) {
                $applicablePassives[$unit->passive_key] = $passive;
            }
        } else {
            // Assign default passive based on sector
            $defaultKey = $this->getDefaultPassiveKeyForSector($unit);
            if ($defaultKey && isset($this->passives[$defaultKey])) {
                $passive = $this->passives[$defaultKey];
                if ($passive->appliesTo($unit)) {
                    $applicablePassives[$defaultKey] = $passive;
                }
            }
        }

        // Always add legendary_aura if unit is legendary
        if ($unit->rarity === 'legendary') {
            $legendaryPassive = $this->passives['legendary_aura'];
            if ($legendaryPassive->appliesTo($unit)) {
                $applicablePassives['legendary_aura'] = $legendaryPassive;
            }
        }

        return $applicablePassives;
    }

    /**
     * Get default passive key based on unit's sector
     *
     * @param SummonedUnit $unit
     * @return string|null Passive key or null
     */
    private function getDefaultPassiveKeyForSector(SummonedUnit $unit): ?string
    {
        if (!$unit->sector) {
            return null;
        }

        return match ($unit->sector->name) {
            'Tech Sector' => 'tech_overclock',
            'Bio Sector' => 'bio_regeneration',
            'Arcane Sector' => 'arcane_surge',
            default => null,
        };
    }

    /**
     * Trigger onBattleStart for all units' passives
     *
     * @param array $battleState Reference to battle state
     * @return void
     */
    public function triggerOnBattleStart(array &$battleState): void
    {
        foreach ($battleState['units'] ?? [] as $unitId => $unitData) {
            foreach ($unitData['passives'] ?? [] as $passive) {
                $passive->onBattleStart($battleState, $unitId);
            }
        }
    }

    /**
     * Trigger beforeUnitActs for a specific unit's passives
     *
     * @param array $battleState Reference to battle state
     * @param string $unitId Internal unit ID
     * @return void
     */
    public function triggerBeforeUnitActs(array &$battleState, string $unitId): void
    {
        foreach ($battleState['units'][$unitId]['passives'] ?? [] as $passive) {
            $passive->beforeUnitActs($battleState, $unitId);
        }
    }

    /**
     * Trigger afterUnitActs for a specific unit's passives
     *
     * @param array $battleState Reference to battle state
     * @param string $unitId Internal unit ID
     * @return void
     */
    public function triggerAfterUnitActs(array &$battleState, string $unitId): void
    {
        foreach ($battleState['units'][$unitId]['passives'] ?? [] as $passive) {
            $passive->afterUnitActs($battleState, $unitId);
        }
    }

    /**
     * Get human-readable description for passive(s) assigned to a unit
     *
     * @param SummonedUnit $unit
     * @return string Combined description of all passives
     */
    public function getPassiveDescription(SummonedUnit $unit): string
    {
        $passives = $this->resolvePassivesForUnit($unit);

        if (empty($passives)) {
            return 'None';
        }

        $descriptions = [];
        foreach ($passives as $key => $passive) {
            $config = config("passives.{$key}");
            if ($config) {
                $descriptions[] = $config['name'] . ': ' . $config['description'];
            }
        }

        return implode(' | ', $descriptions);
    }
}
