<?php

namespace App\Services;

use App\Models\Sector;
use App\Models\User;

class EssenceGenerationService
{
    /**
     * Generate essence rewards for a scan.
     *
     * @param User $user The user performing the scan
     * @param Sector $sector The sector classified from the UPC
     * @param bool $unitSummoned Whether a unit was summoned during this scan
     * @return array Array containing essence rewards data
     */
    public function generateEssenceForScan(User $user, Sector $sector, bool $unitSummoned): array
    {
        $essenceRewards = [];

        // Generate generic essence
        $genericEssence = $this->generateGenericEssence();
        if ($genericEssence > 0) {
            $essenceRewards[] = [
                'type' => 'generic',
                'amount' => $genericEssence,
                'sector_id' => null,
            ];
        }

        // Generate sector-specific essence
        $sectorEssence = $this->generateSectorEssence();
        if ($sectorEssence > 0) {
            $essenceRewards[] = [
                'type' => 'sector',
                'amount' => $sectorEssence,
                'sector_id' => $sector->id,
                'sector_name' => $sector->name,
            ];
        }

        // Generate summon bonus if applicable
        if ($unitSummoned && config('essence.summon_bonus.enabled')) {
            $summonBonus = $this->generateSummonBonus();
            if ($summonBonus > 0) {
                $essenceRewards[] = [
                    'type' => 'summon_bonus',
                    'amount' => $summonBonus,
                    'sector_id' => null,
                ];
            }
        }

        return $essenceRewards;
    }

    /**
     * Generate generic essence based on config chances.
     *
     * @return int The amount of generic essence generated (0 if none)
     */
    protected function generateGenericEssence(): int
    {
        $config = config('essence.generic');

        if (mt_rand(1, 100) / 100 <= $config['chance']) {
            return random_int($config['min'], $config['max']);
        }

        return 0;
    }

    /**
     * Generate sector-specific essence based on config chances.
     *
     * @return int The amount of sector essence generated (0 if none)
     */
    protected function generateSectorEssence(): int
    {
        $config = config('essence.sector');

        if (mt_rand(1, 100) / 100 <= $config['chance']) {
            return random_int($config['min'], $config['max']);
        }

        return 0;
    }

    /**
     * Generate summon bonus essence.
     *
     * @return int The amount of bonus essence generated
     */
    protected function generateSummonBonus(): int
    {
        $config = config('essence.summon_bonus');

        return random_int($config['min'], $config['max']);
    }
}
