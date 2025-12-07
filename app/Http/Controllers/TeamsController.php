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
        $this->authorize('view', $team);

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
        $this->authorize('update', $team);

        $team->load(['units.sector']);

        // Get available units (units not in any team)
        $availableUnits = auth()->user()->summonedUnits()
            ->whereDoesntHave('teams')
            ->orWhereHas('teams', function($query) use ($team) {
                $query->where('team_id', '!=', $team->id);
            })
            ->with('sector')
            ->get();

        return view('teams.edit', compact('team', 'availableUnits'));
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

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
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team deleted successfully!');
    }

    public function addUnit(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:summoned_units,id'],
        ]);

        $unit = SummonedUnit::findOrFail($validated['unit_id']);

        // Verify unit ownership
        if ($unit->user_id !== auth()->id()) {
            abort(403, 'You can only add your own units to teams.');
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

        // Attach unit to team with position
        $team->units()->attach($unit->id, [
            'position' => $team->units()->count() + 1,
        ]);

        return back()->with('success', "{$unit->name} added to team!");
    }

    public function removeUnit(Team $team, SummonedUnit $unit)
    {
        $this->authorize('update', $team);

        // Verify unit ownership
        if ($unit->user_id !== auth()->id()) {
            abort(403, 'You can only remove your own units from teams.');
        }

        $team->units()->detach($unit->id);

        // Reorder positions
        $team->units()->get()->each(function($u, $index) use ($team) {
            $team->units()->updateExistingPivot($u->id, ['position' => $index + 1]);
        });

        return back()->with('success', "{$unit->name} removed from team!");
    }
}
