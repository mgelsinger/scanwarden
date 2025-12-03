<?php

namespace App\Services;

use App\Models\Sector;

class ScanClassificationService
{
    public function classifyUpc(string $upc): Sector
    {
        $sectors = Sector::all();

        if ($sectors->isEmpty()) {
            throw new \RuntimeException('No sectors found in database. Please run seeders.');
        }

        // Use deterministic hashing to classify UPC to a sector
        $hash = crc32($upc);
        $sectorIndex = $hash % $sectors->count();

        $sector = $sectors->get($sectorIndex);

        // Apply prefix-based biases for more realistic classification
        $firstDigit = substr($upc, 0, 1);

        // UPC prefix biases (real-world UPC prefixes loosely correlate with product types)
        $prefixBiases = [
            '0' => 'Food Sector',      // Food and beverages often start with 0
            '3' => 'Bio Sector',        // Health and beauty products
            '4' => 'Industrial Sector', // Household goods and tools
            '6' => 'Household Sector',  // General merchandise
            '7' => 'Tech Sector',       // Electronics
            '8' => 'Arcane Sector',     // Books and media
            '9' => 'Tech Sector',       // Electronics and software
        ];

        if (isset($prefixBiases[$firstDigit])) {
            $biasedSector = $sectors->firstWhere('name', $prefixBiases[$firstDigit]);

            // 60% chance to use the biased sector, 40% to use hash-based
            if ($biasedSector && (($hash % 100) < 60)) {
                $sector = $biasedSector;
            }
        }

        return $sector;
    }

    public function calculateEnergyGain(Sector $sector): int
    {
        // Base energy gain: 10-20 points per scan
        return rand(10, 20);
    }

    public function shouldSummonUnit(int $scansCount): bool
    {
        // Summon chance increases with scans
        // First scan: 100% chance
        // Subsequent scans: 15% base chance + bonus for every 5 scans
        if ($scansCount === 0) {
            return true;
        }

        $baseChance = 15;
        $bonus = floor($scansCount / 5) * 5;
        $totalChance = min($baseChance + $bonus, 50); // Cap at 50%

        return (rand(1, 100) <= $totalChance);
    }
}
