<?php

namespace App\Http\Controllers;

use App\Jobs\ResolveMatchJob;
use App\Models\BattleLog;
use App\Models\BattleMatch;
use App\Models\Team;
use App\Models\User;
use App\Services\BattleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BattleMatchesController extends Controller
{
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
    public function practice(Request $request, BattleResolver $battleResolver)
    {
        $validated = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
        ]);

        $team = Team::findOrFail($validated['team_id']);

        // Verify ownership
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        // Check team has units
        if ($team->units()->count() === 0) {
            return back()->withErrors(['error' => 'Team has no units!']);
        }

        $difficulty = $validated['difficulty'] ?? 'medium';
        $dummyTeam = $battleResolver->generateDummyTeam(3, $difficulty);

        DB::beginTransaction();
        try {
            // Run battle immediately (synchronous for practice)
            $results = $battleResolver->resolveBattle($team, $dummyTeam);

            // Create battle match record
            $match = BattleMatch::create([
                'user_id' => auth()->id(),
                'attacker_team_id' => $team->id,
                'defender_team_id' => null, // No defender team for practice
                'attacker_id' => auth()->id(),
                'defender_id' => null, // AI opponent
                'winner_id' => $results['winner'] === 'attacker' ? auth()->id() : null,
                'winner' => $results['winner'],
                'total_turns' => $results['total_turns'],
                'rating_change' => 0, // No rating change for practice
                'status' => 'completed',
            ]);

            // Save battle logs
            foreach ($results['turns'] as $index => $turn) {
                BattleLog::create([
                    'battle_match_id' => $match->id,
                    'turn_index' => $index,
                    'turn_data' => $turn,
                ]);
            }

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
    public function pvp(Request $request, BattleResolver $battleResolver)
    {
        $validated = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
        ]);

        $attackerTeam = Team::findOrFail($validated['team_id']);

        // Verify ownership
        if ($attackerTeam->user_id !== auth()->id()) {
            abort(403);
        }

        // Check team has units
        if ($attackerTeam->units()->count() === 0) {
            return back()->withErrors(['error' => 'Team has no units!']);
        }

        // Find a random opponent (simple matchmaking)
        $opponent = User::where('id', '!=', auth()->id())
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
            $attacker = auth()->user();

            // Run battle immediately (synchronous)
            $results = $battleResolver->resolveBattle($attackerTeam, $defenderTeam);

            // Determine winner/loser for rating
            $attackerWon = $results['winner'] === 'attacker';
            $isDraw = $results['winner'] === 'draw';

            // Simple rating system: Win +10, Lose -5, Draw 0
            $attackerRatingChange = 0;
            $defenderRatingChange = 0;

            if (!$isDraw) {
                if ($attackerWon) {
                    $attackerRatingChange = 10;
                    $defenderRatingChange = -5;
                } else {
                    $attackerRatingChange = -5;
                    $defenderRatingChange = 10;
                }
            }

            // Update ratings (enforce minimum 0)
            $attackerOldRating = $attacker->rating ?? 1200;
            $defenderOldRating = $opponent->rating ?? 1200;

            $attackerNewRating = max(0, $attackerOldRating + $attackerRatingChange);
            $defenderNewRating = max(0, $defenderOldRating + $defenderRatingChange);

            $attacker->update(['rating' => $attackerNewRating]);
            $opponent->update(['rating' => $defenderNewRating]);

            // Create battle match record
            $winnerId = null;
            if ($attackerWon) {
                $winnerId = $attacker->id;
            } elseif (!$isDraw) {
                $winnerId = $opponent->id;
            }

            $match = BattleMatch::create([
                'user_id' => $attacker->id,
                'attacker_team_id' => $attackerTeam->id,
                'defender_team_id' => $defenderTeam->id,
                'attacker_id' => $attacker->id,
                'defender_id' => $opponent->id,
                'winner_id' => $winnerId,
                'winner' => $results['winner'],
                'total_turns' => $results['total_turns'],
                'rating_change' => $attackerRatingChange,
                'attacker_rating_before' => $attackerOldRating,
                'attacker_rating_after' => $attackerNewRating,
                'defender_rating_before' => $defenderOldRating,
                'defender_rating_after' => $defenderNewRating,
                'status' => 'completed',
            ]);

            // Save battle logs
            foreach ($results['turns'] as $index => $turn) {
                BattleLog::create([
                    'battle_match_id' => $match->id,
                    'turn_index' => $index,
                    'turn_data' => $turn,
                ]);
            }

            DB::commit();

            $message = 'PvP battle completed! ';
            if ($attackerRatingChange > 0) {
                $message .= "You gained {$attackerRatingChange} rating!";
            } elseif ($attackerRatingChange < 0) {
                $message .= "You lost " . abs($attackerRatingChange) . " rating.";
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
