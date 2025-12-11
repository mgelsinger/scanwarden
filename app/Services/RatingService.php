<?php

namespace App\Services;

use App\Models\User;
use App\Services\Battle\BattleResult;

/**
 * RatingService - Centralized PvP rating calculation and updates
 *
 * Handles all rating changes from battle outcomes.
 * Rating changes only apply to PvP battles, not practice battles.
 *
 * Current system: Simple +10 for win, -5 for loss, 0 for draw
 * (Note: ELO-based system was removed in favor of simpler, more predictable system)
 */
class RatingService
{
    /** @var int Default starting rating */
    public const DEFAULT_RATING = 1200;

    /** @var int Rating gain for winning */
    private const WIN_RATING_GAIN = 10;

    /** @var int Rating loss for losing */
    private const LOSS_RATING_PENALTY = 5;

    /** @var int Minimum rating floor */
    private const RATING_FLOOR = 0;

    /**
     * Apply battle result to both users' ratings
     *
     * @param User $attacker The attacking user
     * @param User $defender The defending user
     * @param BattleResult $result The battle resolution result
     * @return array Rating change details: ['attacker' => [...], 'defender' => [...]]
     */
    public function applyBattleResult(User $attacker, User $defender, BattleResult $result): array
    {
        $attackerOldRating = $attacker->rating ?? self::DEFAULT_RATING;
        $defenderOldRating = $defender->rating ?? self::DEFAULT_RATING;

        // Calculate rating changes based on outcome
        [$attackerChange, $defenderChange] = $this->calculateRatingChanges($result->outcome);

        // Apply changes with floor enforcement
        $attackerNewRating = max(self::RATING_FLOOR, $attackerOldRating + $attackerChange);
        $defenderNewRating = max(self::RATING_FLOOR, $defenderOldRating + $defenderChange);

        // Update users
        $attacker->update(['rating' => $attackerNewRating]);
        $defender->update(['rating' => $defenderNewRating]);

        return [
            'attacker' => [
                'old_rating' => $attackerOldRating,
                'new_rating' => $attackerNewRating,
                'change' => $attackerChange,
            ],
            'defender' => [
                'old_rating' => $defenderOldRating,
                'new_rating' => $defenderNewRating,
                'change' => $defenderChange,
            ],
        ];
    }

    /**
     * Calculate rating changes for both players based on outcome
     *
     * @param string $outcome 'attacker_win', 'defender_win', or 'draw'
     * @return array [attackerChange, defenderChange]
     */
    private function calculateRatingChanges(string $outcome): array
    {
        return match ($outcome) {
            'attacker_win' => [self::WIN_RATING_GAIN, -self::LOSS_RATING_PENALTY],
            'defender_win' => [-self::LOSS_RATING_PENALTY, self::WIN_RATING_GAIN],
            'draw' => [0, 0],
            default => [0, 0],
        };
    }

    /**
     * Get default starting rating
     *
     * @return int
     */
    public function getDefaultRating(): int
    {
        return self::DEFAULT_RATING;
    }

    /**
     * Preview rating changes without applying them
     *
     * @param User $attacker
     * @param User $defender
     * @param string $outcome
     * @return array
     */
    public function previewRatingChanges(User $attacker, User $defender, string $outcome): array
    {
        $attackerOldRating = $attacker->rating ?? self::DEFAULT_RATING;
        $defenderOldRating = $defender->rating ?? self::DEFAULT_RATING;

        [$attackerChange, $defenderChange] = $this->calculateRatingChanges($outcome);

        return [
            'attacker' => [
                'old_rating' => $attackerOldRating,
                'new_rating' => max(self::RATING_FLOOR, $attackerOldRating + $attackerChange),
                'change' => $attackerChange,
            ],
            'defender' => [
                'old_rating' => $defenderOldRating,
                'new_rating' => max(self::RATING_FLOOR, $defenderOldRating + $defenderChange),
                'change' => $defenderChange,
            ],
        ];
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
