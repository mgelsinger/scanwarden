<?php

namespace App\Jobs;

use App\Models\BattleMatch;
use App\Models\BattleLog;
use App\Services\BattleSimulatorService;
use App\Services\RatingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ResolveMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public BattleMatch $match
    ) {}

    public function handle(BattleSimulatorService $battleService, RatingService $ratingService): void
    {
        DB::transaction(function () use ($battleService, $ratingService) {
            // Load teams with units and user
            $this->match->load(['attackerTeam.units', 'defenderTeam.units', 'user']);

            // Simulate battle
            $results = $battleService->simulateBattle(
                $this->match->attackerTeam,
                $this->match->defenderTeam
            );

            // Calculate rating gain based on performance
            $ratingGain = $this->calculateRatingGain($results, $this->match);

            // Update user rating
            $user = $this->match->user;
            $oldRating = $user->rating ?? RatingService::DEFAULT_RATING;
            $newRating = $oldRating + $ratingGain;
            $user->update(['rating' => $newRating]);

            // Update match status and winner
            $this->match->update([
                'status' => 'completed',
                'winner' => $results['winner'],
                'total_turns' => $results['total_turns'],
                'rating_change' => $ratingGain,
            ]);

            // Save battle logs
            foreach ($results['turns'] as $turnLog) {
                BattleLog::create([
                    'battle_match_id' => $this->match->id,
                    'turn_number' => $turnLog['turn'],
                    'attacker_name' => $turnLog['attacker'],
                    'attacker_team' => $turnLog['attacker_team'],
                    'defender_name' => $turnLog['defender'],
                    'defender_team' => $turnLog['defender_team'],
                    'damage' => $turnLog['damage'],
                    'defender_hp_after' => $turnLog['defender_hp_after'],
                ]);
            }
        });
    }

    /**
     * Calculate rating gain based on battle performance
     *
     * @param array $results
     * @param BattleMatch $match
     * @return int
     */
    private function calculateRatingGain(array $results, BattleMatch $match): int
    {
        // Base rating for completing a battle
        $baseRating = 5;

        // Efficiency bonus (fewer turns = better)
        $totalTurns = $results['total_turns'];
        $efficiencyBonus = max(0, (50 - $totalTurns) / 5); // Up to +10 for battles under 50 turns

        // Calculate team power for difficulty bonus
        $attackerPower = $match->attackerTeam->units->sum(fn($u) => $u->hp + $u->attack + $u->defense + $u->speed);
        $defenderPower = $match->defenderTeam->units->sum(fn($u) => $u->hp + $u->attack + $u->defense + $u->speed);

        // Difficulty bonus based on power difference (balanced teams give more rating)
        $powerDifference = abs($attackerPower - $defenderPower);
        $averagePower = ($attackerPower + $defenderPower) / 2;
        $balanceRatio = 1 - min(1, $powerDifference / $averagePower);
        $difficultyBonus = $balanceRatio * 10; // Up to +10 for perfectly balanced teams

        // Total rating gain
        return (int)round($baseRating + $efficiencyBonus + $difficultyBonus);
    }
}
