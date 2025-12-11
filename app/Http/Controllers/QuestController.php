<?php

namespace App\Http\Controllers;

use App\Models\UserQuest;
use Illuminate\View\View;

class QuestController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // Fetch active daily quests (not expired)
        $dailyQuests = UserQuest::where('user_id', $user->id)
            ->whereHas('quest', function ($query) {
                $query->where('is_daily', true);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('quest')
            ->orderBy('is_completed', 'asc')
            ->orderBy('assigned_at', 'desc')
            ->get();

        // Fetch achievements
        $achievements = UserQuest::where('user_id', $user->id)
            ->whereHas('quest', function ($query) {
                $query->where('type', 'achievement');
            })
            ->with('quest')
            ->orderBy('is_completed', 'asc')
            ->orderBy('progress', 'desc')
            ->get();

        return view('quests.index', [
            'dailyQuests' => $dailyQuests,
            'achievements' => $achievements,
        ]);
    }
}
