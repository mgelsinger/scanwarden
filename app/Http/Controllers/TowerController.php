<?php

namespace App\Http\Controllers;

use App\Models\BattleMatch;
use App\Models\SectorTower;
use App\Models\Team;
use App\Services\BattleResolver;
use App\Services\SectorTowerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TowerController extends Controller
{
    public function __construct(
        private SectorTowerService $towerService,
        private BattleResolver $battleResolver
    ) {
    }

    /**
     * Display a listing of all towers.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all active towers with their sector
        $towers = SectorTower::with('sector')
            ->where('is_active', true)
            ->get()
            ->map(function ($tower) use ($user) {
                // Get user progress for each tower
                $progress = $this->towerService->getOrCreateProgress($user, $tower);

                return [
                    'tower' => $tower,
                    'highest_floor_cleared' => $progress->highest_floor_cleared,
                    'progress_percentage' => ($progress->highest_floor_cleared / $tower->max_floor) * 100,
                ];
            });

        return view('towers.index', compact('towers'));
    }

    /**
     * Display a specific tower with its stages.
     */
    public function show(Request $request, SectorTower $tower)
    {
        $user = $request->user();

        // Get user progress
        $progress = $this->towerService->getOrCreateProgress($user, $tower);

        // Get all stages ordered by floor
        $stages = $tower->stages()
            ->where('is_active', true)
            ->orderBy('floor')
            ->get()
            ->map(function ($stage) use ($progress) {
                $status = 'locked';

                if ($stage->floor <= $progress->highest_floor_cleared) {
                    $status = 'cleared';
                } elseif ($stage->floor === $progress->highest_floor_cleared + 1) {
                    $status = 'current';
                }

                return [
                    'stage' => $stage,
                    'status' => $status,
                    'locked' => $stage->floor > $progress->highest_floor_cleared + 1,
                    'cleared' => $stage->floor <= $progress->highest_floor_cleared,
                ];
            });

        // Get user's teams for selection
        $teams = $user->teams()->with('units')->get();

        return view('towers.show', compact('tower', 'progress', 'stages', 'teams'));
    }

    /**
     * Fight a tower stage.
     */
    public function fight(Request $request, SectorTower $tower, int $floor)
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $user = $request->user();

        // Load the attacker's team and verify ownership
        $attackerTeam = Team::with('units.sector')
            ->where('id', $validated['team_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Verify team has units
        if ($attackerTeam->units->isEmpty()) {
            return redirect()
                ->route('towers.show', $tower)
                ->with('error', 'Your team has no units. Please add units to your team first.');
        }

        // Load the stage
        $stage = $tower->stages()
            ->where('floor', $floor)
            ->where('is_active', true)
            ->firstOrFail();

        // Get user progress
        $progress = $this->towerService->getOrCreateProgress($user, $tower);

        // Check if user can attempt this floor
        if (!$this->towerService->canAttemptFloor($progress, $stage)) {
            return redirect()
                ->route('towers.show', $tower)
                ->with('error', 'You must clear the previous floor before attempting this one.');
        }

        // Build enemy team
        $defenderTeam = $this->towerService->buildEnemyTeamFromStage($stage);

        // Execute battle in a transaction
        return DB::transaction(function () use ($user, $attackerTeam, $defenderTeam, $tower, $stage) {
            // Resolve battle
            $battleResult = $this->battleResolver->resolve(
                $user,
                $attackerTeam,
                null, // defender user is null for tower
                $defenderTeam
            );

            // Create battle match record
            $battleMatch = BattleMatch::create([
                'user_id' => $user->id,
                'attacker_id' => $user->id,
                'defender_id' => null,
                'attacker_team_id' => $attackerTeam->id,
                'defender_team_id' => null,
                'winner' => $battleResult->getWinnerSide(),
                'total_turns' => $battleResult->totalTurns,
                'attacker_rating_before' => null,
                'attacker_rating_after' => null,
                'defender_rating_before' => null,
                'defender_rating_after' => null,
                'rating_change' => 0,
                'status' => 'completed',
            ]);

            // Handle battle result and update progress
            $towerResult = $this->towerService->handleBattleResult($user, $tower, $stage, $battleResult);

            // Prepare flash data
            $flashData = [
                'battle_result' => $battleResult->getWinnerSide(),
                'floor' => $stage->floor,
                'first_clear' => $towerResult['first_clear'],
                'rewards' => $towerResult['rewards_granted'],
            ];

            if ($towerResult['did_win']) {
                $message = $towerResult['first_clear']
                    ? "Congratulations! You've cleared floor {$stage->floor} for the first time!"
                    : "Victory! You've defeated floor {$stage->floor} again.";

                return redirect()
                    ->route('towers.show', $tower)
                    ->with('status', $message)
                    ->with('tower_result', $flashData);
            } else {
                return redirect()
                    ->route('towers.show', $tower)
                    ->with('error', "Defeated on floor {$stage->floor} – try upgrading your team.")
                    ->with('tower_result', $flashData);
            }
        });
    }
}
