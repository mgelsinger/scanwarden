<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuestProgressService
{
    public function __construct(
        private QuestRewardService $questRewardService
    ) {
    }

    /**
     * Assign daily quests to a user for the current day
     * This is idempotent - calling multiple times per day won't create duplicates
     */
    public function assignDailyQuestsForUser(User $user): void
    {
        $today = Carbon::today();
        $endOfDay = Carbon::today()->endOfDay();

        // Check if user already has active daily quests for today
        $existingDailies = UserQuest::where('user_id', $user->id)
            ->whereHas('quest', function ($query) {
                $query->where('is_daily', true);
            })
            ->where('assigned_at', '>=', $today)
            ->where(function ($query) use ($endOfDay) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        // If user already has dailies assigned for today, skip
        if ($existingDailies > 0) {
            return;
        }

        // Get active daily quests (limit to 3 for now, can make configurable)
        $dailyQuests = Quest::where('is_daily', true)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        foreach ($dailyQuests as $quest) {
            UserQuest::create([
                'user_id' => $user->id,
                'quest_id' => $quest->id,
                'progress' => 0,
                'target_value' => $quest->target_value,
                'is_completed' => false,
                'assigned_at' => now(),
                'expires_at' => $endOfDay,
            ]);
        }
    }

    /**
     * Increment progress for all matching active quests for a user
     *
     * @param User $user The user whose progress to increment
     * @param string $category The quest category (e.g., 'scan', 'battle_pvp_win')
     * @param int $amount The amount to increment by (default 1)
     * @param array $context Additional context (e.g., sector_id, rating)
     */
    public function incrementProgress(User $user, string $category, int $amount = 1, array $context = []): void
    {
        // Find all active user quests matching this category
        $userQuests = UserQuest::where('user_id', $user->id)
            ->where('is_completed', false)
            ->whereHas('quest', function ($query) use ($category) {
                $query->where('category', $category)
                    ->where('is_active', true);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('quest')
            ->get();

        foreach ($userQuests as $userQuest) {
            $this->incrementSingleQuest($userQuest, $amount);
        }
    }

    /**
     * Increment progress for a single user quest
     */
    private function incrementSingleQuest(UserQuest $userQuest, int $amount): void
    {
        DB::transaction(function () use ($userQuest, $amount) {
            // Increment progress, clamped to target value
            $newProgress = min(
                $userQuest->progress + $amount,
                $userQuest->target_value
            );

            $userQuest->update([
                'progress' => $newProgress,
                'last_progress_at' => now(),
            ]);

            // Check if quest is now complete
            if ($newProgress >= $userQuest->target_value && !$userQuest->is_completed) {
                $this->completeQuest($userQuest);
            }
        });
    }

    /**
     * Mark a quest as completed and grant rewards
     */
    public function completeQuest(UserQuest $userQuest): void
    {
        // If already completed, skip
        if ($userQuest->is_completed) {
            return;
        }

        DB::transaction(function () use ($userQuest) {
            // Mark as completed
            $userQuest->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // Grant rewards
            $this->questRewardService->grantRewards($userQuest->user, $userQuest->quest);
        });
    }

    /**
     * Get recently completed quests for a user (useful for notifications)
     *
     * @param User $user
     * @param Carbon $since
     * @return \Illuminate\Support\Collection
     */
    public function recentlyCompletedForUser(User $user, Carbon $since)
    {
        return UserQuest::where('user_id', $user->id)
            ->where('is_completed', true)
            ->where('completed_at', '>=', $since)
            ->with('quest')
            ->get();
    }
}
