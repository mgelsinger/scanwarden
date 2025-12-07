<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScanClassificationService;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __construct(
        private ScanClassificationService $scanService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'upc' => ['required', 'string', 'regex:/^[0-9]{12,13}$/'],
        ]);

        try {
            $result = $this->scanService->processScan(
                auth()->user(),
                $validated['upc']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index()
    {
        $scans = auth()->user()->scanRecords()
            ->with('sector')
            ->latest()
            ->paginate(20);

        return response()->json($scans);
    }
}
