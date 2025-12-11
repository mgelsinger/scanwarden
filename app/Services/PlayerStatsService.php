<?php

namespace App\Services;

use App\Models\BattleMatch;
use App\Models\User;

class PlayerStatsService
{
    /**
     * Get battle statistics for a user
     *
     * @param User $user
     * @return array
     */
    public function getStatsForUser(User $user): array
    {
        // Get all battles where user participated
        $battles = BattleMatch::query()
            ->where(function ($query) use ($user) {
                $query->where('attacker_id', $user->id)
                    ->orWhere('defender_id', $user->id);
            })
            ->get();

        $totalBattles = $battles->count();
        $wins = 0;
        $losses = 0;
        $draws = 0;

        foreach ($battles as $battle) {
            if ($battle->winner_id === null) {
                $draws++;
            } elseif ($battle->winner_id === $user->id) {
                $wins++;
            } else {
                $losses++;
            }
        }

        $winRate = $totalBattles > 0 ? round(($wins / $totalBattles) * 100, 1) : 0;

        return [
            'total_battles' => $totalBattles,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'win_rate' => $winRate,
        ];
    }

    /**
     * Get leaderboard entries with stats
     *
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @return array
     */
    public function enrichUsersWithStats($users): array
    {
        return $users->map(function ($user) {
            $stats = $this->getStatsForUser($user);
            return [
                'user' => $user,
                'stats' => $stats,
            ];
        })->toArray();
    }
}
