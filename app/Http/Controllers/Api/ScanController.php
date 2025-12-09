<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScanRecord;
use App\Models\SectorEnergy;
use App\Models\UserEssence;
use App\Services\EssenceGenerationService;
use App\Services\LoreService;
use App\Services\ScanClassificationService;
use App\Services\UnitSummoningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function __construct(
        private ScanClassificationService $classificationService,
        private UnitSummoningService $summoningService,
        private LoreService $loreService,
        private EssenceGenerationService $essenceService
    ) {}

    public function store(Request $request)
    {
        // Remove all whitespace
        $cleanedUpc = preg_replace('/\s+/', '', (string) $request->input('upc', ''));
        $request->merge(['upc' => $cleanedUpc]);

        $validated = $request->validate([
            'upc' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9]+$/'],
        ]);

        $user = auth()->user();

        try {
            DB::beginTransaction();

            // Classify the UPC to a sector
            $sector = $this->classificationService->classifyUpc($validated['upc']);

            // Calculate energy gain
            $energyGain = $this->classificationService->calculateEnergyGain($sector);

            // Update or create sector energy
            $sectorEnergy = SectorEnergy::firstOrCreate(
                ['user_id' => $user->id, 'sector_id' => $sector->id],
                ['current_energy' => 0]
            );
            $sectorEnergy->increment('current_energy', $energyGain);

            // Check if we should summon a unit
            $scansCount = $user->scanRecords()->count();
            $shouldSummon = $this->classificationService->shouldSummonUnit($scansCount);

            // Summon unit if triggered
            $summonedUnitData = null;
            if ($shouldSummon) {
                $summonedUnit = $this->summoningService->summonUnit($user, $sector, $validated['upc']);
                $summonedUnitData = $this->summoningService->getUnitSummary($summonedUnit);
            }

            // Generate and award essence
            $essenceRewards = $this->essenceService->generateEssenceForScan($user, $sector, $shouldSummon);
            foreach ($essenceRewards as $essenceReward) {
                UserEssence::addEssence(
                    $user->id,
                    $essenceReward['amount'],
                    $essenceReward['type'],
                    $essenceReward['sector_id'] ?? null
                );
            }

            // Prepare rewards
            $rewards = [
                'energy_gained' => $energyGain,
                'sector_name' => $sector->name,
                'should_summon' => $shouldSummon,
                'summoned_unit' => $summonedUnitData,
                'essence_rewards' => $essenceRewards,
            ];

            // Create scan record
            $scanRecord = ScanRecord::create([
                'user_id' => $user->id,
                'raw_upc' => $validated['upc'],
                'sector_id' => $sector->id,
                'rewards' => $rewards,
            ]);

            // Check and unlock any lore entries
            $this->loreService->checkAndUnlockLore($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'scan_record' => $scanRecord->load('sector'),
                    'rewards' => $rewards,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index()
    {
        $scans = auth()->user()->scanRecords()
            ->with('sector')
            ->latest()
            ->paginate(20);

        return response()->json($scans);
    }
}
