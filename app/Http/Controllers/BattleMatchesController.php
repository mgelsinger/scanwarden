<?php

namespace App\Http\Controllers;

use App\Jobs\ResolveMatchJob;
use App\Models\BattleMatch;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BattleMatchesController extends Controller
{
    public function index(): View
    {
        $matches = auth()->user()->battleMatches()
            ->with(['attackerTeam', 'defenderTeam'])
            ->latest()
            ->paginate(20);

        return view('battles.index', compact('matches'));
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
}
