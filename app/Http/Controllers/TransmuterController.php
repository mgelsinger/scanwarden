<?php

namespace App\Http\Controllers;

use App\Exceptions\CannotAffordTransmutationException;
use App\Exceptions\InsufficientResourcesException;
use App\Models\TransmutationRecipe;
use App\Services\EssenceTransmuterService;
use App\Services\QuestProgressService;
use Illuminate\Http\Request;

class TransmuterController extends Controller
{
    public function __construct(
        private EssenceTransmuterService $transmuterService,
        private QuestProgressService $questProgressService
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

        // Ensure recipe is active
        if (!$recipe->is_active) {
            return back()->with('error', 'This recipe is not available.');
        }

        try {
            $result = $this->transmuterService->transmute(auth()->user(), $recipe);

            // Increment quest progress for transmuter usage
            $this->questProgressService->incrementProgress(auth()->user(), 'transmuter_use', 1);

            return back()->with('success', 'Transmutation successful!')
                ->with('transmutation_result', $result);

        } catch (CannotAffordTransmutationException $e) {
            return back()->with('error', 'You do not have enough essence or energy for this recipe.');
        } catch (InsufficientResourcesException $e) {
            return back()->with('error', 'Insufficient resources: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Transmutation failed: ' . $e->getMessage());
        }
    }
}
