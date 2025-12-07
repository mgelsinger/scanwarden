<?php

namespace App\Services;

use App\Models\TransmutationRecipe;
use App\Models\User;
use App\Models\UserEssence;
use App\Models\SummonedUnit;
use App\Models\TransmutationHistory;
use Illuminate\Support\Facades\DB;

class EssenceTransmuterService
{
    public function __construct(
        private UnitSummoningService $summoningService
    ) {}

    public function canAffordRecipe(User $user, TransmutationRecipe $recipe): bool
    {
        foreach ($recipe->required_inputs as $input) {
            if ($input['type'] === 'essence') {
                $essence = UserEssence::where('user_id', $user->id)
                    ->where('sector_id', $input['sector_id'] ?? null)
                    ->where('type', $input['essence_type'] ?? 'generic')
                    ->first();

                if (!$essence || $essence->amount < $input['amount']) {
                    return false;
                }
            }

            if ($input['type'] === 'sector_energy') {
                $energy = $user->sectorEnergies()
                    ->where('sector_id', $input['sector_id'])
                    ->first();

                if (!$energy || $energy->current_energy < $input['amount']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function transmute(User $user, TransmutationRecipe $recipe): array
    {
        if (!$this->canAffordRecipe($user, $recipe)) {
            throw new \Exception('Insufficient resources for this transmutation.');
        }

        DB::beginTransaction();

        try {
            // Consume inputs
            $inputsConsumed = [];
            foreach ($recipe->required_inputs as $input) {
                if ($input['type'] === 'essence') {
                    $essence = UserEssence::where('user_id', $user->id)
                        ->where('sector_id', $input['sector_id'] ?? null)
                        ->where('type', $input['essence_type'] ?? 'generic')
                        ->firstOrFail();

                    $essence->decrement('amount', $input['amount']);
                    $inputsConsumed[] = $input;
                }

                if ($input['type'] === 'sector_energy') {
                    $energy = $user->sectorEnergies()
                        ->where('sector_id', $input['sector_id'])
                        ->firstOrFail();

                    $energy->decrement('current_energy', $input['amount']);
                    $inputsConsumed[] = $input;
                }
            }

            // Grant outputs
            $outputsReceived = [];
            foreach ($recipe->outputs as $output) {
                if ($output['type'] === 'essence') {
                    $essence = UserEssence::firstOrCreate([
                        'user_id' => $user->id,
                        'sector_id' => $output['sector_id'] ?? null,
                        'type' => $output['essence_type'] ?? 'generic',
                    ], ['amount' => 0]);

                    $essence->increment('amount', $output['amount']);
                    $outputsReceived[] = $output;
                }

                if ($output['type'] === 'unit_summon') {
                    $sector = \App\Models\Sector::findOrFail($output['sector_id']);
                    $unit = $this->summoningService->summonUnitWithRarity(
                        $user,
                        $sector,
                        'transmute-' . time(),
                        $output['rarity'] ?? 'rare'
                    );
                    $outputsReceived[] = [
                        'type' => 'unit',
                        'unit' => $unit->toArray(),
                    ];
                }

                if ($output['type'] === 'sector_energy') {
                    $energy = $user->sectorEnergies()->firstOrCreate([
                        'sector_id' => $output['sector_id'],
                    ], ['current_energy' => 0]);

                    $energy->increment('current_energy', $output['amount']);
                    $outputsReceived[] = $output;
                }
            }

            // Record history
            TransmutationHistory::create([
                'user_id' => $user->id,
                'recipe_id' => $recipe->id,
                'inputs_consumed' => $inputsConsumed,
                'outputs_received' => $outputsReceived,
            ]);

            DB::commit();

            return [
                'success' => true,
                'inputs' => $inputsConsumed,
                'outputs' => $outputsReceived,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
