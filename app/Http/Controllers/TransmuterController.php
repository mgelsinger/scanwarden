<?php

namespace App\Http\Controllers;

use App\Models\TransmutationRecipe;
use App\Services\EssenceTransmuterService;
use Illuminate\Http\Request;

class TransmuterController extends Controller
{
    public function __construct(
        private EssenceTransmuterService $transmuterService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $recipes = TransmutationRecipe::where('is_active', true)
            ->with('sector')
            ->get();

        // Check affordability for each recipe
        $recipes->each(function($recipe) use ($user) {
            $recipe->can_afford = $this->transmuterService->canAffordRecipe($user, $recipe);
        });

        $userEssence = $user->essence()->with('sector')->get();

        return view('transmuter.index', [
            'recipes' => $recipes,
            'userEssence' => $userEssence,
        ]);
    }

    public function transmute(Request $request, TransmutationRecipe $recipe)
    {
        $validated = $request->validate([
            'confirm' => 'required|accepted',
        ]);

        try {
            $result = $this->transmuterService->transmute(auth()->user(), $recipe);

            return back()->with('success', 'Transmutation successful!')
                ->with('transmutation_result', $result);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
