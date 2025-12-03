<?php

namespace App\Http\Controllers;

use App\Models\LoreEntry;
use App\Models\Sector;
use App\Services\LoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoreController extends Controller
{
    public function __construct(
        private LoreService $loreService
    ) {}

    public function index(Request $request): View
    {
        $sectors = Sector::all();
        $sectorFilter = $request->query('sector');

        $loreEntries = $this->loreService->getLoreWithStatus(
            auth()->user(),
            $sectorFilter
        );

        // Separate locked and unlocked
        $unlockedLore = $loreEntries->where('is_unlocked', true);
        $lockedLore = $loreEntries->where('is_unlocked', false);

        return view('lore.index', compact('unlockedLore', 'lockedLore', 'sectors', 'sectorFilter'));
    }

    public function show(LoreEntry $lore): View
    {
        $isUnlocked = auth()->user()->unlockedLore()->where('lore_entry_id', $lore->id)->exists();

        if (!$isUnlocked) {
            abort(403, 'This lore entry has not been unlocked yet.');
        }

        $lore->load('sector');

        return view('lore.show', compact('lore'));
    }
}
