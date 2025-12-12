<?php

namespace App\Http\Controllers;

use App\Models\UserTowerProgress;
use App\Services\QuestProgressService;
use App\Services\RatingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private RatingService $ratingService,
        private QuestProgressService $questProgressService
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        // Assign daily quests if not already assigned today
        $this->questProgressService->assignDailyQuestsForUser($user);

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

        // Determine next action for onboarding
        $hasTowerProgress = UserTowerProgress::where('user_id', $user->id)
            ->where('highest_floor_cleared', '>', 0)
            ->exists();

        $nextAction = 'pvp_battle'; // default
        if ($stats['total_scans'] === 0) {
            $nextAction = 'scan_item';
        } elseif ($stats['total_units'] > 0 && $stats['total_teams'] === 0) {
            $nextAction = 'build_team';
        } elseif ($stats['total_teams'] > 0 && !$hasTowerProgress) {
            $nextAction = 'enter_tower';
        }

        $playerStatus = [
            'total_scans' => $stats['total_scans'],
            'total_units' => $stats['total_units'],
            'total_teams' => $stats['total_teams'],
            'has_tower_progress' => $hasTowerProgress,
            'next_action' => $nextAction,
        ];

        return view('dashboard', compact(
            'stats',
            'sectorEnergies',
            'recentUnits',
            'recentBattles',
            'evolvableUnits',
            'playerStatus'
        ));
    }
}
