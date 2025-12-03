<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RatingService;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        private RatingService $ratingService
    ) {}

    public function index(): View
    {
        $leaderboard = User::query()
            ->whereNotNull('rating')
            ->where('rating', '>', 0)
            ->orderBy('rating', 'desc')
            ->paginate(50);

        // Add tier information to each user
        $leaderboard->getCollection()->transform(function ($user) {
            $user->tier = $this->ratingService->getRatingTier($user->rating);
            $user->tier_color = $this->ratingService->getRatingTierColor($user->rating);
            return $user;
        });

        // Get current user's rank if authenticated
        $currentUserRank = null;
        if (auth()->check() && auth()->user()->rating) {
            $currentUserRank = User::where('rating', '>', auth()->user()->rating)->count() + 1;
        }

        return view('leaderboard.index', compact('leaderboard', 'currentUserRank'));
    }
}
