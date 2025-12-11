<?php

namespace App\Http\Controllers;

use App\Jobs\ResolveMatchJob;
use App\Models\BattleLog;
use App\Models\BattleMatch;
use App\Models\Team;
use App\Models\User;
use App\Services\BattleResolver;
use App\Services\QuestProgressService;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BattleMatchesController extends Controller
{
    public function __construct(
        private QuestProgressService $questProgressService,
        private BattleResolver $battleResolver,
        private RatingService $ratingService
    ) {
    }

    public function index(): View
    {
        $user = auth()->user();
        $rating = $user->rating ?? 1200;

        $teams = $user->teams()
            ->withCount('units')
            ->get();

        $matches = $user->battleMatches()
            ->with(['attackerTeam', 'defenderTeam', 'attacker', 'defender'])
            ->latest()
            ->paginate(20);

        return view('battles.index', compact('matches', 'rating', 'teams'));
    }

    public function create(): View
    {
        $teams = auth()->user()->teams()
            ->withCount('units')
            ->get();

        return view('battles.create', compact('teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attacker_team_id' => ['required', 'exists:teams,id'],
            'defender_team_id' => ['required', 'exists:teams,id', 'different:attacker_team_id'],
        ], [
            'defender_team_id.different' => 'You cannot battle the same team against itself.',
        ]);

        $attackerTeam = Team::findOrFail($validated['attacker_team_id']);
        $defenderTeam = Team::findOrFail($validated['defender_team_id']);

        // Verify ownership
        if ($attackerTeam->user_id !== auth()->id() || $defenderTeam->user_id !== auth()->id()) {
            abort(403);
        }

        // Check teams have units
        if ($attackerTeam->units()->count() === 0) {
            return back()->withErrors(['error' => 'Attacker team has no units!']);
        }

        if ($defenderTeam->units()->count() === 0) {
            return back()->withErrors(['error' => 'Defender team has no units!']);
        }

        // Create battle match
        $match = BattleMatch::create([
            'user_id' => auth()->id(),
            'attacker_team_id' => $attackerTeam->id,
            'defender_team_id' => $defenderTeam->id,
            'status' => 'pending',
        ]);

        // Dispatch job to resolve battle
        ResolveMatchJob::dispatch($match);

        return redirect()
            ->route('battles.show', $match)
            ->with('success', 'Battle initiated! Processing results...');
    }

    public function show(BattleMatch $match): View
    {
        // Authorization check
        if ($match->user_id !== auth()->id()) {
            abort(403);
        }

        $match->load([
            'attackerTeam.units.sector',
            'defenderTeam.units.sector',
            'battleLogs'
        ]);

        return view('battles.show', compact('match'));
    }

    public function destroy(BattleMatch $match)
    {
        // Authorization check
        if ($match->user_id !== auth()->id()) {
            abort(403);
        }

        $match->delete();

        return redirect()
            ->route('battles.index')
            ->with('success', 'Battle record deleted successfully!');
    }

    /**
     * Initiate a practice battle vs AI
     */
    public function practice(Request $request)
    {
        $validated = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
        ]);

        $team = Team::findOrFail($validated['team_id']);
        $user = auth()->user();

        // Verify ownership
        if ($team->user_id !== $user->id) {
            abort(403);
        }

        // Check team has units
        if ($team->units()->count() === 0) {
            return back()->withErrors(['error' => 'Team has no units!']);
        }

        $difficulty = $validated['difficulty'] ?? 'medium';
        $dummyTeam = $this->battleResolver->generateDummyTeam(3, $difficulty);

        DB::beginTransaction();
        try {
            // Run battle using new standardized API
            $result = $this->battleResolver->resolve($user, $team, null, $dummyTeam);

            // Create battle match record
            $match = BattleMatch::create([
                'user_id' => $user->id,
                'attacker_team_id' => $team->id,
                'defender_team_id' => null, // No defender team for practice
                'attacker_id' => $user->id,
                'defender_id' => null, // AI opponent
                'winner_id' => $result->attackerWon() ? $user->id : null,
                'winner' => $result->getWinnerSide(),
                'total_turns' => $result->totalTurns,
                'rating_change' => 0, // No rating change for practice
                'status' => 'completed',
            ]);

            // Save battle logs
            foreach ($result->turns as $index => $turn) {
                BattleLog::create([
                    'battle_match_id' => $match->id,
                    'turn_index' => $index,
                    'turn_data' => $turn,
                ]);
            }

            // Increment quest progress for practice battles
            $this->questProgressService->incrementProgress($user, 'battle_practice', 1);

            DB::commit();

            return redirect()
                ->route('battles.show', $match)
                ->with('success', 'Practice battle completed!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Battle failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Initiate a PvP battle vs another player
     */
    public function pvp(Request $request)
    {
        $validated = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
        ]);

        $attackerTeam = Team::findOrFail($validated['team_id']);
        $attacker = auth()->user();

        // Verify ownership
        if ($attackerTeam->user_id !== $attacker->id) {
            abort(403);
        }

        // Check team has units
        if ($attackerTeam->units()->count() === 0) {
            return back()->withErrors(['error' => 'Team has no units!']);
        }

        // Find a random opponent (simple matchmaking)
        $opponent = User::where('id', '!=', $attacker->id)
            ->whereHas('teams.units') // Has at least one team with units
            ->inRandomOrder()
            ->first();

        if (!$opponent) {
            return back()->withErrors(['error' => 'No opponents available. Try again later!']);
        }

        // Get a random team from opponent
        $defenderTeam = $opponent->teams()
            ->whereHas('units')
            ->inRandomOrder()
            ->first();

        if (!$defenderTeam) {
            return back()->withErrors(['error' => 'Opponent has no valid teams!']);
        }

        DB::beginTransaction();
        try {
            // Run battle using new standardized API
            $result = $this->battleResolver->resolve($attacker, $attackerTeam, $opponent, $defenderTeam);

            // Apply rating changes using RatingService
            $ratings = $this->ratingService->applyBattleResult($attacker, $opponent, $result);

            // Create battle match record
            $match = BattleMatch::create([
                'user_id' => $attacker->id,
                'attacker_team_id' => $attackerTeam->id,
                'defender_team_id' => $defenderTeam->id,
                'attacker_id' => $attacker->id,
                'defender_id' => $opponent->id,
                'winner_id' => $result->winnerUserId,
                'winner' => $result->getWinnerSide(),
                'total_turns' => $result->totalTurns,
                'rating_change' => $ratings['attacker']['change'],
                'attacker_rating_before' => $ratings['attacker']['old_rating'],
                'attacker_rating_after' => $ratings['attacker']['new_rating'],
                'defender_rating_before' => $ratings['defender']['old_rating'],
                'defender_rating_after' => $ratings['defender']['new_rating'],
                'status' => 'completed',
            ]);

            // Save battle logs
            foreach ($result->turns as $index => $turn) {
                BattleLog::create([
                    'battle_match_id' => $match->id,
                    'turn_index' => $index,
                    'turn_data' => $turn,
                ]);
            }

            // Increment quest progress for PvP battles
            $this->questProgressService->incrementProgress($attacker, 'battle_pvp', 1);
            $this->questProgressService->incrementProgress($opponent, 'battle_pvp', 1);

            // Increment win quest for winner
            if ($result->attackerWon()) {
                $this->questProgressService->incrementProgress($attacker, 'battle_pvp_win', 1);
            } elseif ($result->defenderWon()) {
                $this->questProgressService->incrementProgress($opponent, 'battle_pvp_win', 1);
            }

            DB::commit();

            // Generate success message
            $message = 'PvP battle completed! ';
            $ratingChange = $ratings['attacker']['change'];
            if ($ratingChange > 0) {
                $message .= "You gained {$ratingChange} rating!";
            } elseif ($ratingChange < 0) {
                $message .= "You lost " . abs($ratingChange) . " rating.";
            } else {
                $message .= "Draw - no rating change.";
            }

            return redirect()
                ->route('battles.show', $match)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Battle failed: ' . $e->getMessage()]);
        }
    }
}
