<?php

namespace App\Services;

use App\Models\EvolutionRule;
use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\User;
use App\Models\UserEssence;

class EvolutionService
{
    public function __construct(
        private QuestProgressService $questProgressService
    ) {
    }

    public function canEvolve(SummonedUnit $unit, User $user): bool
    {
        $requirements = $this->getEvolutionRequirements($unit);

        if (!$requirements) {
            return false;
        }

        // Check generic essence
        if ($requirements['required_generic_essence'] > 0) {
            $genericEssence = UserEssence::where('user_id', $user->id)
                ->where('type', 'generic')
                ->whereNull('sector_id')
                ->first();

            if (!$genericEssence || $genericEssence->amount < $requirements['required_generic_essence']) {
                return false;
            }
        }

        // Check sector essence
        if ($requirements['required_sector_essence'] > 0) {
            $sectorEssence = UserEssence::where('user_id', $user->id)
                ->where('type', 'sector')
                ->where('sector_id', $unit->sector_id)
                ->first();

            if (!$sectorEssence || $sectorEssence->amount < $requirements['required_sector_essence']) {
                return false;
            }
        }

        // Check sector energy
        if ($requirements['required_energy'] > 0) {
            $sectorEnergy = SectorEnergy::where('user_id', $user->id)
                ->where('sector_id', $unit->sector_id)
                ->first();

            if (!$sectorEnergy || $sectorEnergy->current_energy < $requirements['required_energy']) {
                return false;
            }
        }

        return true;
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
            'required_generic_essence' => $rule->required_generic_essence ?? 0,
            'required_sector_essence' => $rule->required_sector_essence ?? 0,
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

        // Deduct generic essence
        if ($requirements['required_generic_essence'] > 0) {
            $genericEssence = UserEssence::where('user_id', $user->id)
                ->where('type', 'generic')
                ->whereNull('sector_id')
                ->firstOrFail();
            $genericEssence->decrement('amount', $requirements['required_generic_essence']);
        }

        // Deduct sector essence
        if ($requirements['required_sector_essence'] > 0) {
            $sectorEssence = UserEssence::where('user_id', $user->id)
                ->where('type', 'sector')
                ->where('sector_id', $unit->sector_id)
                ->firstOrFail();
            $sectorEssence->decrement('amount', $requirements['required_sector_essence']);
        }

        // Deduct sector energy
        if ($requirements['required_energy'] > 0) {
            $sectorEnergy = SectorEnergy::where('user_id', $user->id)
                ->where('sector_id', $unit->sector_id)
                ->firstOrFail();
            $sectorEnergy->decrement('current_energy', $requirements['required_energy']);
        }

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

        // Increment quest progress for evolution
        $this->questProgressService->incrementProgress($user, 'evolution', 1);

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
            'required_generic_essence' => $requirements['required_generic_essence'],
            'required_sector_essence' => $requirements['required_sector_essence'],
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
