<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = auth()->user()->scanRecords()
            ->with('sector')
            ->latest();

        // Optional sector filter
        if ($request->filled('sector')) {
            $query->where('sector_id', $request->sector);
        }

        $scans = $query->paginate(20);
        $sectors = Sector::all();

        return view('scan-history.index', [
            'scans' => $scans,
            'sectors' => $sectors,
            'selectedSector' => $request->sector,
        ]);
    }
}
