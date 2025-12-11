<?php

namespace App\Http\Controllers;

use App\Models\ScanRecord;
use App\Models\SectorEnergy;
use App\Models\UserEssence;
use App\Services\EssenceGenerationService;
use App\Services\LoreService;
use App\Services\QuestProgressService;
use App\Services\ScanClassificationService;
use App\Services\UnitSummoningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function __construct(
        private ScanClassificationService $classificationService,
        private UnitSummoningService $summoningService,
        private LoreService $loreService,
        private EssenceGenerationService $essenceService,
        private QuestProgressService $questProgressService
    ) {}

    public function create(): View
    {
        return view('scan.create');
    }

    public function store(Request $request)
    {
        // Remove all whitespace (leading, trailing, internal)
        $cleanedUpc = preg_replace('/\s+/', '', (string) $request->input('upc', ''));
        $request->merge([
            'upc' => $cleanedUpc,
        ]);

        $validated = $request->validate([
            'upc' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9]+$/'],
        ], [
            'upc.required' => 'Please enter a UPC code.',
            'upc.regex' => 'UPC must contain only numbers.',
            'upc.min' => 'UPC must be at least 8 digits.',
            'upc.max' => 'UPC must be no more than 20 digits.',
        ]);

        $user = auth()->user();

        try {
            DB::beginTransaction();

            // Classify the UPC to a sector
            $sector = $this->classificationService->classifyUpc($validated['upc']);

            // Calculate energy gain
            $energyGain = $this->classificationService->calculateEnergyGain($sector);

            // Update or create sector energy for this user
            $sectorEnergy = SectorEnergy::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'sector_id' => $sector->id,
                ],
                [
                    'current_energy' => 0,
                ]
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

            // Increment quest progress for scans
            $this->questProgressService->incrementProgress($user, 'scan', 1, [
                'sector_id' => $sector->id,
            ]);

            DB::commit();

            return redirect()
                ->route('scan.result', $scanRecord)
                ->with('success', 'Scan completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Scan failed: ' . $e->getMessage()]);
        }
    }

    public function result(ScanRecord $scanRecord): View
    {
        // Ensure user can only see their own scans
        if ($scanRecord->user_id !== auth()->id()) {
            abort(403);
        }

        $scanRecord->load('sector');

        return view('scan.result', [
            'scanRecord' => $scanRecord,
            'sector' => $scanRecord->sector,
            'rewards' => $scanRecord->rewards,
        ]);
    }
}
