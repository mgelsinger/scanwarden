<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\SummonedUnit;

class UnitController extends Controller
{
    public function index()
    {
        $units = auth()->user()->summonedUnits()
            ->with('sector')
            ->paginate(20);

        return UnitResource::collection($units);
    }

    public function show(SummonedUnit $unit)
    {
        if ($unit->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'This unit does not belong to you.'
            ], 403);
        }

        return new UnitResource($unit->load('sector'));
    }
}
