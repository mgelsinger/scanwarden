<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\SummonedUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StarterController extends Controller
{
    /**
     * Show the starter selection page.
     */
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // If user already has a starter or any unit, redirect to dashboard
        if ($user->summonedUnits()->count() > 0) {
            return redirect()->route('dashboard')
                ->with('info', 'You have already chosen your starter!');
        }

        // Get starter templates from config
        $starters = collect(config('starters.templates'));

        // Get all sectors for lookup
        $sectors = Sector::all()->keyBy('slug');

        // Attach sector data to each starter
        $starters = $starters->map(function ($starter) use ($sectors) {
            $starter['sector'] = $sectors->get($starter['sector_slug']);
            return $starter;
        });

        return view('starter.index', [
            'starters' => $starters,
        ]);
    }

    /**
     * Handle starter selection.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Prevent selecting starter if user already has units
        if ($user->summonedUnits()->count() > 0) {
            return redirect()->route('dashboard')
                ->with('error', 'You have already chosen your starter!');
        }

        // Validate the selected starter
        $validated = $request->validate([
            'starter_key' => ['required', 'string', 'in:' . $this->getValidStarterKeys()],
        ]);

        try {
            DB::beginTransaction();

            // Get the starter template
            $starters = collect(config('starters.templates'));
            $template = $starters->firstWhere('key', $validated['starter_key']);

            if (!$template) {
                throw new \Exception('Invalid starter selected.');
            }

            // Get the sector
            $sector = Sector::where('slug', $template['sector_slug'])->firstOrFail();

            // Create the starter unit
            $unit = SummonedUnit::create([
                'user_id' => $user->id,
                'sector_id' => $sector->id,
                'name' => $template['name'],
                'rarity' => $template['rarity'],
                'tier' => $template['tier'],
                'evolution_level' => 0,
                'hp' => $template['hp'],
                'attack' => $template['attack'],
                'defense' => $template['defense'],
                'speed' => $template['speed'],
                'passive_ability' => $template['passive_ability'],
                'source' => 'starter',
            ]);

            // Grant some initial sector energy
            $user->sectorEnergies()->updateOrCreate(
                ['sector_id' => $sector->id],
                ['current_energy' => DB::raw('current_energy + 50')]
            );

            DB::commit();

            return redirect()->route('dashboard')
                ->with('success', "Welcome, Warden! {$template['name']} has joined your ranks!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to select starter: ' . $e->getMessage());
        }
    }

    /**
     * Get comma-separated list of valid starter keys for validation.
     */
    private function getValidStarterKeys(): string
    {
        return collect(config('starters.templates'))
            ->pluck('key')
            ->implode(',');
    }
}
