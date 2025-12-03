<?php

namespace App\Http\Controllers;

use App\Services\RatingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private RatingService $ratingService
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        // Gather statistics
        $stats = [
            'total_scans' => $user->scanRecords()->count(),
            'total_units' => $user->summonedUnits()->count(),
            'total_teams' => $user->teams()->count(),
            'total_battles' => $user->battleMatches()->where('status', 'completed')->count(),
            'rating' => $user->rating ?? 1200,
            'unlocked_lore' => $user->unlockedLore()->count(),
        ];

        // Get rating tier info
        $stats['rating_tier'] = $this->ratingService->getRatingTier($stats['rating']);
        $stats['rating_color'] = $this->ratingService->getRatingTierColor($stats['rating']);

        // Get sector energy totals
        $sectorEnergies = $user->sectorEnergies()->with('sector')->get();

        // Get recent units
        $recentUnits = $user->summonedUnits()
            ->with('sector')
            ->latest()
            ->limit(5)
            ->get();

        // Get recent battles
        $recentBattles = $user->battleMatches()
            ->with(['attackerTeam', 'defenderTeam'])
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        // Get units that can evolve
        $evolvableUnits = $user->summonedUnits()
            ->whereHas('evolutionRuleForCurrentTier')
            ->with('sector')
            ->limit(3)
            ->get();

        return view('dashboard', compact(
            'stats',
            'sectorEnergies',
            'recentUnits',
            'recentBattles',
            'evolvableUnits'
        ));
    }
}
