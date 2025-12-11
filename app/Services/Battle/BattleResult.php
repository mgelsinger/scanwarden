<?php

namespace App\Services\Battle;

/**
 * BattleResult - Data transfer object for battle resolution results
 *
 * This DTO encapsulates all information about a resolved battle,
 * including outcome, winner, turn-by-turn logs, and final unit states.
 */
class BattleResult
{
    /**
     * @param string $outcome Battle outcome: 'attacker_win', 'defender_win', or 'draw'
     * @param int|null $winnerUserId ID of the winning user, null for draws or AI battles
     * @param array $turns Turn-by-turn battle log entries
     * @param array $finalStates Final HP and status for each unit
     * @param int $totalTurns Total number of turns executed
     * @param int $attackerSurvivors Number of attacker units still alive
     * @param int $defenderSurvivors Number of defender units still alive
     */
    public function __construct(
        public readonly string $outcome,
        public readonly ?int $winnerUserId,
        public readonly array $turns,
        public readonly array $finalStates,
        public readonly int $totalTurns,
        public readonly int $attackerSurvivors,
        public readonly int $defenderSurvivors
    ) {
    }

    /**
     * Get legacy-compatible winner string
     *
     * @return string 'attacker', 'defender', or 'draw'
     */
    public function getWinnerSide(): string
    {
        return match ($this->outcome) {
            'attacker_win' => 'attacker',
            'defender_win' => 'defender',
            default => 'draw',
        };
    }

    /**
     * Check if battle was a draw
     *
     * @return bool
     */
    public function isDraw(): bool
    {
        return $this->outcome === 'draw';
    }

    /**
     * Check if attacker won
     *
     * @return bool
     */
    public function attackerWon(): bool
    {
        return $this->outcome === 'attacker_win';
    }

    /**
     * Check if defender won
     *
     * @return bool
     */
    public function defenderWon(): bool
    {
        return $this->outcome === 'defender_win';
    }

    /**
     * Convert to array format for backwards compatibility
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome,
            'winner' => $this->getWinnerSide(),
            'winner_user_id' => $this->winnerUserId,
            'turns' => $this->turns,
            'final_states' => $this->finalStates,
            'total_turns' => $this->totalTurns,
            'attacker_survivors' => $this->attackerSurvivors,
            'defender_survivors' => $this->defenderSurvivors,
        ];
    }
}
