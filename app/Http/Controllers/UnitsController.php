<?php

namespace App\Http\Controllers;

use App\Models\SectorEnergy;
use App\Models\SummonedUnit;
use App\Models\UserEssence;
use App\Services\EvolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitsController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService
    ) {}

    public function index(Request $request): View
    {
        $query = auth()->user()->summonedUnits()->with('sector');

        // Filter by sector
        if ($request->filled('sector')) {
            $query->where('sector_id', $request->sector);
        }

        // Filter by rarity
        if ($request->filled('rarity')) {
            $query->where('rarity', $request->rarity);
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');

        $allowedSorts = ['created_at', 'name', 'tier', 'rarity', 'hp', 'attack', 'defense', 'speed'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $units = $query->get();

        // Add evolution info to each unit
        foreach ($units as $unit) {
            $unit->can_evolve = $this->evolutionService->canEvolve($unit, auth()->user());
            $unit->evolution_requirements = $this->evolutionService->getEvolutionRequirements($unit);
        }

        return view('units.index', [
            'units' => $units,
            'filters' => [
                'sector' => $request->sector,
                'rarity' => $request->rarity,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    public function show(SummonedUnit $unit): View
    {
        // Ensure user owns this unit
        if ($unit->user_id !== auth()->id()) {
            abort(403);
        }

        $unit->load('sector');

        $canEvolve = $this->evolutionService->canEvolve($unit, auth()->user());
        $requirements = $this->evolutionService->getEvolutionRequirements($unit);
        $preview = $this->evolutionService->getEvolutionPreview($unit);

        // Get user's sector energy
        $sectorEnergy = SectorEnergy::where('user_id', auth()->id())
            ->where('sector_id', $unit->sector_id)
            ->first();

        // Get user's essence balances
        $genericEssence = UserEssence::where('user_id', auth()->id())
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $sectorEssence = UserEssence::where('user_id', auth()->id())
            ->where('type', 'sector')
            ->where('sector_id', $unit->sector_id)
            ->first();

        return view('units.show', [
            'unit' => $unit,
            'canEvolve' => $canEvolve,
            'requirements' => $requirements,
            'preview' => $preview,
            'userSectorEnergy' => $sectorEnergy?->current_energy ?? 0,
            'userGenericEssence' => $genericEssence?->amount ?? 0,
            'userSectorEssence' => $sectorEssence?->amount ?? 0,
        ]);
    }

    public function evolve(SummonedUnit $unit)
    {
        // Ensure user owns this unit
        if ($unit->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $oldTier = $unit->tier;
            $evolvedUnit = $this->evolutionService->evolveUnit($unit, auth()->user());

            DB::commit();

            return redirect()
                ->route('units.show', $evolvedUnit)
                ->with('success', "Unit evolved from Tier {$oldTier} to Tier {$evolvedUnit->tier}!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
