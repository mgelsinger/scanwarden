<?php

namespace App\Http\Controllers;

use App\Models\BattleMatch;
use App\Models\SummonedUnit;
use App\Models\User;
use App\Services\PlayerStatsService;
use App\Services\RatingService;
use Illuminate\View\View;

class PlayerProfileController extends Controller
{
    public function __construct(
        private PlayerStatsService $statsService,
        private RatingService $ratingService
    ) {}

    public function show(User $user): View
    {
        // Get battle stats
        $stats = $this->statsService->getStatsForUser($user);

        // Get rating tier
        $tier = $this->ratingService->getRatingTier($user->rating ?? 0);
        $tierColor = $this->ratingService->getRatingTierColor($user->rating ?? 0);

        // Get recent battles (last 10)
        $recentBattles = BattleMatch::query()
            ->where(function ($query) use ($user) {
                $query->where('attacker_id', $user->id)
                    ->orWhere('defender_id', $user->id);
            })
            ->with(['attacker', 'defender', 'attackerTeam', 'defenderTeam'])
            ->latest()
            ->take(10)
            ->get();

        // Get user's teams with units
        $teams = $user->teams()
            ->withCount('units')
            ->with(['units' => function ($query) {
                $query->with('sector')->take(5); // Limit to first 5 units per team
            }])
            ->take(5) // Limit to 5 teams
            ->get();

        // Get sector distribution of units
        $sectorDistribution = SummonedUnit::query()
            ->selectRaw('sector_id, count(*) as count')
            ->where('user_id', $user->id)
            ->groupBy('sector_id')
            ->with('sector')
            ->get();

        // Check if viewing own profile
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;

        return view('players.show', compact(
            'user',
            'stats',
            'tier',
            'tierColor',
            'recentBattles',
            'teams',
            'sectorDistribution',
            'isOwnProfile'
        ));
    }
}
