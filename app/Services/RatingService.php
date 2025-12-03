<?php

namespace App\Services;

use App\Models\User;

class RatingService
{
    /**
     * K-factor for ELO rating system
     * Higher K = more volatile rating changes
     */
    private const K_FACTOR = 32;

    /**
     * Default starting rating for new players
     */
    public const DEFAULT_RATING = 1200;

    /**
     * Calculate new ratings after a battle
     *
     * @param User $winner
     * @param User $loser
     * @return array ['winner_new_rating' => int, 'loser_new_rating' => int, 'winner_change' => int, 'loser_change' => int]
     */
    public function calculateNewRatings(User $winner, User $loser): array
    {
        $winnerRating = $winner->rating ?? self::DEFAULT_RATING;
        $loserRating = $loser->rating ?? self::DEFAULT_RATING;

        // Calculate expected scores
        $winnerExpected = $this->getExpectedScore($winnerRating, $loserRating);
        $loserExpected = $this->getExpectedScore($loserRating, $winnerRating);

        // Calculate new ratings
        // Winner gets 1 point, loser gets 0 points
        $winnerNewRating = (int)round($winnerRating + self::K_FACTOR * (1 - $winnerExpected));
        $loserNewRating = (int)round($loserRating + self::K_FACTOR * (0 - $loserExpected));

        return [
            'winner_new_rating' => $winnerNewRating,
            'loser_new_rating' => $loserNewRating,
            'winner_change' => $winnerNewRating - $winnerRating,
            'loser_change' => $loserNewRating - $loserRating,
        ];
    }

    /**
     * Calculate expected score for a player
     * Formula: 1 / (1 + 10^((opponentRating - playerRating) / 400))
     *
     * @param int $playerRating
     * @param int $opponentRating
     * @return float
     */
    private function getExpectedScore(int $playerRating, int $opponentRating): float
    {
        return 1 / (1 + pow(10, ($opponentRating - $playerRating) / 400));
    }

    /**
     * Update ratings for both players after a battle
     *
     * @param User $winner
     * @param User $loser
     * @return array
     */
    public function updateRatings(User $winner, User $loser): array
    {
        $ratings = $this->calculateNewRatings($winner, $loser);

        $winner->update(['rating' => $ratings['winner_new_rating']]);
        $loser->update(['rating' => $ratings['loser_new_rating']]);

        return $ratings;
    }

    /**
     * Get rating tier name based on rating value
     *
     * @param int $rating
     * @return string
     */
    public function getRatingTier(int $rating): string
    {
        return match (true) {
            $rating >= 2000 => 'Legend',
            $rating >= 1800 => 'Master',
            $rating >= 1600 => 'Diamond',
            $rating >= 1400 => 'Platinum',
            $rating >= 1200 => 'Gold',
            $rating >= 1000 => 'Silver',
            default => 'Bronze',
        };
    }

    /**
     * Get rating tier color for display
     *
     * @param int $rating
     * @return string
     */
    public function getRatingTierColor(int $rating): string
    {
        return match (true) {
            $rating >= 2000 => '#FFD700', // Legend - Gold
            $rating >= 1800 => '#9B59B6', // Master - Purple
            $rating >= 1600 => '#3498DB', // Diamond - Blue
            $rating >= 1400 => '#1ABC9C', // Platinum - Teal
            $rating >= 1200 => '#F39C12', // Gold - Orange
            $rating >= 1000 => '#95A5A6', // Silver - Silver
            default => '#CD7F32', // Bronze - Bronze
        };
    }
}
