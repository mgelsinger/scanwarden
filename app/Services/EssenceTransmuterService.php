<?php

namespace App\Services;

use App\Exceptions\CannotAffordTransmutationException;
use App\Models\TransmutationRecipe;
use App\Models\User;
use App\Models\TransmutationHistory;
use Illuminate\Support\Facades\DB;

class EssenceTransmuterService
{
    public function __construct(
        private UnitSummoningService $summoningService,
        private ResourceService $resourceService
    ) {}

    public function canAffordRecipe(User $user, TransmutationRecipe $recipe): bool
    {
        foreach ($recipe->required_inputs as $input) {
            if ($input['type'] === 'essence') {
                $essenceType = $input['essence_type'] ?? 'generic';

                if ($essenceType === 'generic') {
                    if (!$this->resourceService->userHasGenericEssence($user, $input['amount'])) {
                        return false;
                    }
                } else {
                    // Sector-specific essence
                    $sectorId = $input['sector_id'] ?? null;
                    if (!$sectorId || !$this->resourceService->userHasSectorEssence($user, $sectorId, $input['amount'])) {
                        return false;
                    }
                }
            }

            if ($input['type'] === 'sector_energy') {
                if (!$this->resourceService->userHasSectorEnergy($user, $input['sector_id'], $input['amount'])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function transmute(User $user, TransmutationRecipe $recipe): array
    {
        if (!$this->canAffordRecipe($user, $recipe)) {
            throw new CannotAffordTransmutationException('Insufficient resources for this transmutation.');
        }

        DB::beginTransaction();

        try {
            // Consume inputs
            $inputsConsumed = [];
            foreach ($recipe->required_inputs as $input) {
                if ($input['type'] === 'essence') {
                    $essenceType = $input['essence_type'] ?? 'generic';

                    if ($essenceType === 'generic') {
                        $this->resourceService->deductGenericEssence($user, $input['amount']);
                    } else {
                        // Sector-specific essence
                        $sectorId = $input['sector_id'] ?? null;
                        if ($sectorId) {
                            $this->resourceService->deductSectorEssence($user, $sectorId, $input['amount']);
                        }
                    }

                    $inputsConsumed[] = $input;
                }

                if ($input['type'] === 'sector_energy') {
                    $this->resourceService->deductSectorEnergy($user, $input['sector_id'], $input['amount']);
                    $inputsConsumed[] = $input;
                }
            }

            // Grant outputs
            $outputsReceived = [];
            foreach ($recipe->outputs as $output) {
                if ($output['type'] === 'essence') {
                    $essenceType = $output['essence_type'] ?? 'generic';

                    if ($essenceType === 'generic') {
                        $this->resourceService->grantGenericEssence($user, $output['amount']);
                    } else {
                        // Sector-specific essence
                        $sectorId = $output['sector_id'] ?? null;
                        if ($sectorId) {
                            $this->resourceService->grantSectorEssence($user, $sectorId, $output['amount']);
                        }
                    }

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
                    $this->resourceService->grantSectorEnergy($user, $output['sector_id'], $output['amount']);
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
