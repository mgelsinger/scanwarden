<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\SummonedUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamsController extends Controller
{
    public function index(): View
    {
        $teams = auth()->user()->teams()
            ->withCount('units')
            ->latest()
            ->get();

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = auth()->user()->teams()->create([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('teams.show', $team)
            ->with('success', 'Team created successfully!');
    }

    public function show(Team $team): View
    {
        // Authorization check
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        $team->load(['units.sector']);

        // Get available units (not in any team or in this team)
        $availableUnits = auth()->user()->summonedUnits()
            ->whereDoesntHave('teams', function ($query) use ($team) {
                $query->where('team_id', '!=', $team->id);
            })
            ->with('sector')
            ->get();

        return view('teams.show', compact('team', 'availableUnits'));
    }

    public function edit(Team $team): View
    {
        // Authorization check
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        return view('teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team)
    {
        // Authorization check
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team->update($validated);

        return redirect()
            ->route('teams.show', $team)
            ->with('success', 'Team updated successfully!');
    }

    public function destroy(Team $team)
    {
        // Authorization check
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team deleted successfully!');
    }

    public function addUnit(Request $request, Team $team)
    {
        // Authorization check
        if ($team->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:summoned_units,id'],
        ]);

        $unit = SummonedUnit::findOrFail($validated['unit_id']);

        // Verify unit ownership
        if ($unit->user_id !== auth()->id()) {
            abort(403);
        }

        // Check team size limit
        if ($team->units()->count() >= 5) {
            return back()->withErrors(['error' => 'Team is full! Maximum 5 units per team.']);
        }

        // Check if unit is already in this team
        if ($team->units()->where('summoned_unit_id', $unit->id)->exists()) {
            return back()->withErrors(['error' => 'Unit is already in this team.']);
        }

        // Check if unit is in another team
        if ($unit->teams()->where('team_id', '!=', $team->id)->exists()) {
            return back()->withErrors(['error' => 'Unit is already assigned to another team.']);
        }

        $team->units()->attach($unit->id);

        return back()->with('success', "{$unit->name} added to team!");
    }

    public function removeUnit(Team $team, SummonedUnit $unit)
    {
        // Authorization check
        if ($team->user_id !== auth()->id() || $unit->user_id !== auth()->id()) {
            abort(403);
        }

        $team->units()->detach($unit->id);

        return back()->with('success', "{$unit->name} removed from team!");
    }
}
