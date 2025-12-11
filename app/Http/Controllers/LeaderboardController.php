<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlayerStatsService;
use App\Services\RatingService;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        private RatingService $ratingService,
        private PlayerStatsService $statsService
    ) {}

    public function index(): View
    {
        $users = User::query()
            ->orderBy('rating', 'desc')
            ->orderBy('id')
            ->take(100)
            ->get();

        // Enrich users with stats and tier information
        $leaders = $users->map(function ($user) {
            $stats = $this->statsService->getStatsForUser($user);
            return [
                'user' => $user,
                'stats' => $stats,
                'tier' => $this->ratingService->getRatingTier($user->rating ?? 0),
                'tier_color' => $this->ratingService->getRatingTierColor($user->rating ?? 0),
            ];
        });

        // Get current user's rank if authenticated
        $currentUserRank = null;
        if (auth()->check()) {
            $userRating = auth()->user()->rating ?? 0;
            $currentUserRank = User::where('rating', '>', $userRating)->count() + 1;
        }

        return view('leaderboard.index', compact('leaders', 'currentUserRank'));
    }
}
