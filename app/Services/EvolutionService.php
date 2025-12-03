<?php

namespace App\Services;

use App\Models\EvolutionRule;
use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\User;

class EvolutionService
{
    public function canEvolve(SummonedUnit $unit, User $user): bool
    {
        $requirements = $this->getEvolutionRequirements($unit);

        if (!$requirements) {
            return false;
        }

        // Check if user has enough sector energy
        $sectorEnergy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $unit->sector_id)
            ->first();

        if (!$sectorEnergy) {
            return false;
        }

        return $sectorEnergy->current_energy >= $requirements['required_energy'];
    }

    public function getEvolutionRequirements(SummonedUnit $unit): ?array
    {
        $rule = EvolutionRule::where('from_tier', $unit->tier)->first();

        if (!$rule) {
            return null;
        }

        return [
            'to_tier' => $rule->to_tier,
            'required_energy' => $rule->required_sector_energy,
            'hp_multiplier' => $rule->hp_multiplier,
            'attack_multiplier' => $rule->attack_multiplier,
            'defense_multiplier' => $rule->defense_multiplier,
            'speed_multiplier' => $rule->speed_multiplier,
            'new_name_suffix' => $rule->new_name_suffix,
            'new_trait' => $rule->new_trait,
        ];
    }

    public function evolveUnit(SummonedUnit $unit, User $user): SummonedUnit
    {
        if (!$this->canEvolve($unit, $user)) {
            throw new \Exception('Unit cannot be evolved. Requirements not met.');
        }

        $requirements = $this->getEvolutionRequirements($unit);

        // Get sector energy
        $sectorEnergy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $unit->sector_id)
            ->firstOrFail();

        // Deduct energy
        $sectorEnergy->decrement('current_energy', $requirements['required_energy']);

        // Calculate new stats
        $newHp = (int)($unit->hp * $requirements['hp_multiplier']);
        $newAttack = (int)($unit->attack * $requirements['attack_multiplier']);
        $newDefense = (int)($unit->defense * $requirements['defense_multiplier']);
        $newSpeed = (int)($unit->speed * $requirements['speed_multiplier']);

        // Update name if suffix provided
        $newName = $unit->name;
        if ($requirements['new_name_suffix']) {
            // Remove old suffix if exists
            $baseName = preg_replace('/ (Elite|Champion|Legend)$/', '', $unit->name);
            $newName = $baseName . ' ' . $requirements['new_name_suffix'];
        }

        // Update or set passive ability
        $newPassiveAbility = $unit->passive_ability;
        if ($requirements['new_trait'] && !$unit->passive_ability) {
            $newPassiveAbility = $requirements['new_trait'];
        }

        // Update unit
        $unit->update([
            'tier' => $requirements['to_tier'],
            'evolution_level' => $unit->evolution_level + 1,
            'name' => $newName,
            'hp' => $newHp,
            'attack' => $newAttack,
            'defense' => $newDefense,
            'speed' => $newSpeed,
            'passive_ability' => $newPassiveAbility,
        ]);

        return $unit->fresh();
    }

    public function getEvolutionPreview(SummonedUnit $unit): ?array
    {
        $requirements = $this->getEvolutionRequirements($unit);

        if (!$requirements) {
            return null;
        }

        return [
            'current_tier' => $unit->tier,
            'next_tier' => $requirements['to_tier'],
            'required_energy' => $requirements['required_energy'],
            'current_stats' => [
                'hp' => $unit->hp,
                'attack' => $unit->attack,
                'defense' => $unit->defense,
                'speed' => $unit->speed,
            ],
            'new_stats' => [
                'hp' => (int)($unit->hp * $requirements['hp_multiplier']),
                'attack' => (int)($unit->attack * $requirements['attack_multiplier']),
                'defense' => (int)($unit->defense * $requirements['defense_multiplier']),
                'speed' => (int)($unit->speed * $requirements['speed_multiplier']),
            ],
            'stat_gains' => [
                'hp' => (int)($unit->hp * $requirements['hp_multiplier']) - $unit->hp,
                'attack' => (int)($unit->attack * $requirements['attack_multiplier']) - $unit->attack,
                'defense' => (int)($unit->defense * $requirements['defense_multiplier']) - $unit->defense,
                'speed' => (int)($unit->speed * $requirements['speed_multiplier']) - $unit->speed,
            ],
            'new_trait' => $requirements['new_trait'],
        ];
    }
}
