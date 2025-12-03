<?php

namespace App\Services;

use App\Models\LoreEntry;
use App\Models\User;

class LoreService
{
    /**
     * Check and unlock any lore entries that the user has met requirements for
     *
     * @param User $user
     * @return array Array of newly unlocked lore entries
     */
    public function checkAndUnlockLore(User $user): array
    {
        $unlockedLore = [];

        // Get all lore entries that user hasn't unlocked yet
        $availableLore = LoreEntry::whereDoesntHave('unlockedByUsers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        foreach ($availableLore as $lore) {
            if ($this->meetsUnlockRequirement($user, $lore)) {
                $user->unlockedLore()->attach($lore->id, [
                    'unlocked_at' => now(),
                ]);
                $unlockedLore[] = $lore;
            }
        }

        return $unlockedLore;
    }

    /**
     * Check if user meets the unlock requirement for a lore entry
     *
     * @param User $user
     * @param LoreEntry $lore
     * @return bool
     */
    private function meetsUnlockRequirement(User $user, LoreEntry $lore): bool
    {
        if (!$lore->unlock_key) {
            return true; // No requirements, always unlocked
        }

        return match ($lore->unlock_key) {
            'scan_count' => $user->scanRecords()->count() >= $lore->unlock_threshold,
            'unit_tier' => $user->summonedUnits()->where('tier', '>=', $lore->unlock_threshold)->exists(),
            'evolution_level' => $user->summonedUnits()->where('evolution_level', '>=', $lore->unlock_threshold)->exists(),
            'battle_wins' => $this->getBattleWins($user) >= $lore->unlock_threshold,
            'total_rating' => ($user->rating ?? 0) >= $lore->unlock_threshold,
            default => false,
        };
    }

    /**
     * Get the number of battle wins for a user
     *
     * @param User $user
     * @return int
     */
    private function getBattleWins(User $user): int
    {
        return $user->battleMatches()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get unlock progress for a specific lore entry
     *
     * @param User $user
     * @param LoreEntry $lore
     * @return array
     */
    public function getUnlockProgress(User $user, LoreEntry $lore): array
    {
        if (!$lore->unlock_key) {
            return [
                'unlocked' => true,
                'current' => 1,
                'required' => 1,
                'progress' => 100,
            ];
        }

        $current = match ($lore->unlock_key) {
            'scan_count' => $user->scanRecords()->count(),
            'unit_tier' => $user->summonedUnits()->max('tier') ?? 0,
            'evolution_level' => $user->summonedUnits()->max('evolution_level') ?? 0,
            'battle_wins' => $this->getBattleWins($user),
            'total_rating' => $user->rating ?? 0,
            default => 0,
        };

        $required = $lore->unlock_threshold;
        $unlocked = $current >= $required;
        $progress = $required > 0 ? min(100, ($current / $required) * 100) : 100;

        return [
            'unlocked' => $unlocked,
            'current' => $current,
            'required' => $required,
            'progress' => (int)round($progress),
        ];
    }

    /**
     * Get all lore entries with unlock status for a user
     *
     * @param User $user
     * @param int|null $sectorId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLoreWithStatus(User $user, ?int $sectorId = null)
    {
        $query = LoreEntry::with('sector');

        if ($sectorId) {
            $query->where('sector_id', $sectorId);
        }

        $lore = $query->get();

        // Add unlock status to each entry
        $lore->each(function ($entry) use ($user) {
            $entry->is_unlocked = $user->unlockedLore()->where('lore_entry_id', $entry->id)->exists();
            $entry->progress = $this->getUnlockProgress($user, $entry);
        });

        return $lore;
    }
}
