<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\SummonedUnit;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = auth()->user()->teams()
            ->withCount('units')
            ->get();

        return TeamResource::collection($teams);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = auth()->user()->teams()->create([
            'name' => $validated['name'],
        ]);

        return new TeamResource($team);
    }

    public function show(Team $team)
    {
        if ($team->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        return new TeamResource($team->load('units.sector'));
    }

    public function update(Request $request, Team $team)
    {
        if ($team->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team->update($validated);

        return new TeamResource($team);
    }

    public function destroy(Team $team)
    {
        if ($team->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        $team->delete();

        return response()->json([
            'message' => 'Team deleted successfully'
        ]);
    }

    public function addUnit(Request $request, Team $team)
    {
        if ($team->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:summoned_units,id'],
        ]);

        $unit = SummonedUnit::findOrFail($validated['unit_id']);

        if ($unit->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        if ($team->units()->count() >= 5) {
            return response()->json([
                'message' => 'Team is full (max 5 units)'
            ], 422);
        }

        $team->units()->attach($unit->id, [
            'position' => $team->units()->count() + 1,
        ]);

        return new TeamResource($team->load('units.sector'));
    }

    public function removeUnit(Team $team, SummonedUnit $unit)
    {
        if ($team->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        if ($unit->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'forbidden'
            ], 403);
        }

        $team->units()->detach($unit->id);

        // Reorder positions
        $team->units()->get()->each(function($u, $index) use ($team) {
            $team->units()->updateExistingPivot($u->id, ['position' => $index + 1]);
        });

        return new TeamResource($team->load('units.sector'));
    }
}
