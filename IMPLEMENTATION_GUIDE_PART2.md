# ScanWarden Implementation Guide - Part 2 (Phases 3-6)

This is a continuation of the main implementation guide.

---

## PHASE 3 — Parties/Teams UI Enhancement (CONTINUED)

### Step 3.2: Update TeamsController

Add methods for adding/removing units:

```php
public function edit(Team $team)
{
    $this->authorize('update', $team);

    $availableUnits = auth()->user()->summonedUnits()
        ->whereDoesntHave('teams')
        ->orWhereHas('teams', function($query) use ($team) {
            $query->where('team_id', '!=', $team->id);
        })
        ->get();

    $team->load(['units.sector']);

    return view('teams.edit', [
        'team' => $team,
        'availableUnits' => $availableUnits,
    ]);
}

public function addUnit(Request $request, Team $team)
{
    $this->authorize('update', $team);

    $validated = $request->validate([
        'unit_id' => ['required', 'exists:summoned_units,id'],
    ]);

    $unit = SummonedUnit::findOrFail($validated['unit_id']);

    // Verify ownership
    if ($unit->user_id !== auth()->id()) {
        abort(403);
    }

    // Check team size
    if ($team->units()->count() >= 5) {
        return back()->with('error', 'Team is full (max 5 units)');
    }

    // Attach unit to team
    $team->units()->attach($unit->id, [
        'position' => $team->units()->count() + 1,
    ]);

    return back()->with('success', "{$unit->name} added to team!");
}

public function removeUnit(Team $team, SummonedUnit $unit)
{
    $this->authorize('update', $team);

    if ($unit->user_id !== auth()->id()) {
        abort(403);
    }

    $team->units()->detach($unit->id);

    // Reorder positions
    $team->units()->get()->each(function($u, $index) use ($team) {
        $team->units()->updateExistingPivot($u->id, ['position' => $index + 1]);
    });

    return back()->with('success', "{$unit->name} removed from team!");
}
```

### Step 3.3: Create Team Policy

File: `app/Policies/TeamPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->id === $team->user_id;
    }

    public function update(User $user, Team $team): bool
    {
        return $user->id === $team->user_id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->id === $team->user_id;
    }
}
```

Register in `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    Team::class => TeamPolicy::class,
];
```

---

## PHASE 4 — Scan History Page

### Step 4.1: Create Scan History Controller

File: `app/Http/Controllers/ScanHistoryController.php`

```php
<?php

namespace App\Http\Controllers;

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

        $sectors = \App\Models\Sector::all();

        return view('scan-history.index', [
            'scans' => $scans,
            'sectors' => $sectors,
        ]);
    }
}
```

### Step 4.2: Add Route

In `routes/web.php`:

```php
Route::middleware(['auth', 'starter.selected'])->group(function () {
    // ... existing routes ...

    Route::get('/scan-history', [ScanHistoryController::class, 'index'])->name('scan-history.index');
});
```

### Step 4.3: Create View

File: `resources/views/scan-history/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Scan History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" action="{{ route('scan-history.index') }}" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Sector</label>
                        <select name="sector" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Sectors</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->id }}" {{ request('sector') == $sector->id ? 'selected' : '' }}>
                                    {{ $sector->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Filter
                    </button>
                    @if(request()->filled('sector'))
                        <a href="{{ route('scan-history.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Scan List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UPC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sector</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rewards</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($scans as $scan)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm text-gray-900">{{ $scan->raw_upc }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded" style="background-color: {{ $scan->sector->color }}; color: white;">
                                        {{ $scan->sector->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        @if(isset($scan->rewards['energy_gained']))
                                            <div>+{{ $scan->rewards['energy_gained'] }} Energy</div>
                                        @endif
                                        @if($scan->rewards['should_summon'] ?? false)
                                            <div class="text-green-600 font-semibold">
                                                ✓ Summoned: {{ $scan->rewards['summoned_unit']['name'] ?? 'Unit' }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $scan->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No scans found. Start scanning to build your history!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $scans->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
```

### Step 4.4: Add Navigation Link

In `resources/views/layouts/navigation.blade.php`:

```blade
<x-nav-link :href="route('scan-history.index')" :active="request()->routeIs('scan-history.*')">
    {{ __('Scan History') }}
</x-nav-link>
```

---

## PHASE 5 — Essence Transmuter System

### Step 5.1: Create Essence Migration

```bash
php artisan make:migration create_essence_and_transmutation_tables
```

File: `database/migrations/XXXX_create_essence_and_transmutation_tables.php`

```php
public function up(): void
{
    Schema::create('user_essence', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('sector_id')->nullable()->constrained()->onDelete('cascade');
        $table->integer('amount')->default(0);
        $table->string('type')->default('generic'); // 'generic', 'sector'
        $table->timestamps();

        $table->unique(['user_id', 'sector_id', 'type']);
    });

    Schema::create('transmutation_recipes', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description');
        $table->json('required_inputs'); // [{type: 'essence', amount: 100, sector_id: 1}, ...]
        $table->json('outputs'); // [{type: 'unit_summon', rarity: 'rare'}, ...]
        $table->foreignId('sector_id')->nullable()->constrained()->onDelete('cascade');
        $table->boolean('is_active')->default(true);
        $table->integer('level_requirement')->default(1);
        $table->timestamps();
    });

    Schema::create('transmutation_history', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('recipe_id')->constrained('transmutation_recipes')->onDelete('cascade');
        $table->json('inputs_consumed');
        $table->json('outputs_received');
        $table->timestamps();
    });
}
```

### Step 5.2: Create Models

File: `app/Models/UserEssence.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEssence extends Model
{
    protected $table = 'user_essence';

    protected $fillable = [
        'user_id',
        'sector_id',
        'amount',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
```

File: `app/Models/TransmutationRecipe.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmutationRecipe extends Model
{
    protected $fillable = [
        'name',
        'description',
        'required_inputs',
        'outputs',
        'sector_id',
        'is_active',
        'level_requirement',
    ];

    protected $casts = [
        'required_inputs' => 'array',
        'outputs' => 'array',
        'is_active' => 'boolean',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
```

### Step 5.3: Create Essence Service

File: `app/Services/EssenceTransmuterService.php`

```php
<?php

namespace App\Services;

use App\Models\TransmutationRecipe;
use App\Models\User;
use App\Models\UserEssence;
use App\Models\SummonedUnit;
use Illuminate\Support\Facades\DB;

class EssenceTransmuterService
{
    public function __construct(
        private UnitSummoningService $summoningService
    ) {}

    public function canAffordRecipe(User $user, TransmutationRecipe $recipe): bool
    {
        foreach ($recipe->required_inputs as $input) {
            if ($input['type'] === 'essence') {
                $essence = UserEssence::where('user_id', $user->id)
                    ->where('sector_id', $input['sector_id'] ?? null)
                    ->where('type', $input['essence_type'] ?? 'generic')
                    ->first();

                if (!$essence || $essence->amount < $input['amount']) {
                    return false;
                }
            }

            if ($input['type'] === 'sector_energy') {
                $energy = $user->sectorEnergies()
                    ->where('sector_id', $input['sector_id'])
                    ->first();

                if (!$energy || $energy->current_energy < $input['amount']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function transmute(User $user, TransmutationRecipe $recipe): array
    {
        if (!$this->canAffordRecipe($user, $recipe)) {
            throw new \Exception('Insufficient resources for this transmutation.');
        }

        DB::beginTransaction();

        try {
            // Consume inputs
            $inputsConsumed = [];
            foreach ($recipe->required_inputs as $input) {
                if ($input['type'] === 'essence') {
                    $essence = UserEssence::where('user_id', $user->id)
                        ->where('sector_id', $input['sector_id'] ?? null)
                        ->where('type', $input['essence_type'] ?? 'generic')
                        ->firstOrFail();

                    $essence->decrement('amount', $input['amount']);
                    $inputsConsumed[] = $input;
                }

                if ($input['type'] === 'sector_energy') {
                    $energy = $user->sectorEnergies()
                        ->where('sector_id', $input['sector_id'])
                        ->firstOrFail();

                    $energy->decrement('current_energy', $input['amount']);
                    $inputsConsumed[] = $input;
                }
            }

            // Grant outputs
            $outputsReceived = [];
            foreach ($recipe->outputs as $output) {
                if ($output['type'] === 'essence') {
                    $essence = UserEssence::firstOrCreate([
                        'user_id' => $user->id,
                        'sector_id' => $output['sector_id'] ?? null,
                        'type' => $output['essence_type'] ?? 'generic',
                    ], ['amount' => 0]);

                    $essence->increment('amount', $output['amount']);
                    $outputsReceived[] = $output;
                }

                if ($output['type'] === 'unit_summon') {
                    $sector = \App\Models\Sector::findOrFail($output['sector_id']);
                    $unit = $this->summoningService->summonUnitWithRarity(
                        $user,
                        $sector,
                        'transmute-' . time(),
                        $output['rarity'] ?? 'rare'
                    );
                    $outputsReceived[] = [
                        'type' => 'unit',
                        'unit' => $unit->toArray(),
                    ];
                }

                if ($output['type'] === 'sector_energy') {
                    $energy = $user->sectorEnergies()->firstOrCreate([
                        'sector_id' => $output['sector_id'],
                    ], ['current_energy' => 0]);

                    $energy->increment('current_energy', $output['amount']);
                    $outputsReceived[] = $output;
                }
            }

            // Record history
            \App\Models\TransmutationHistory::create([
                'user_id' => $user->id,
                'recipe_id' => $recipe->id,
                'inputs_consumed' => $inputsConsumed,
                'outputs_received' => $outputsReceived,
            ]);

            DB::commit();

            return [
                'success' => true,
                'inputs' => $inputsConsumed,
                'outputs' => $outputsReceived,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Step 5.4: Create Transmuter Controller

File: `app/Http/Controllers/TransmuterController.php`

```php
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
```

### Step 5.5: Create Seeder for Recipes

File: `database/seeders/TransmutationRecipeSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\TransmutationRecipe;
use Illuminate\Database\Seeder;

class TransmutationRecipeSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = Sector::all();

        // Generic recipes
        TransmutationRecipe::create([
            'name' => 'Essence Consolidation',
            'description' => 'Convert 100 generic essence into 50 sector-specific essence',
            'required_inputs' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100]
            ],
            'outputs' => [
                ['type' => 'essence', 'essence_type' => 'sector', 'amount' => 50, 'sector_id' => null]
            ],
            'is_active' => true,
            'level_requirement' => 1,
        ]);

        // Sector-specific summon recipes
        foreach ($sectors as $sector) {
            TransmutationRecipe::create([
                'name' => "Summon {$sector->name} Rare",
                'description' => "Guaranteed Rare unit from {$sector->name} sector",
                'sector_id' => $sector->id,
                'required_inputs' => [
                    ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 200]
                ],
                'outputs' => [
                    ['type' => 'unit_summon', 'sector_id' => $sector->id, 'rarity' => 'rare']
                ],
                'is_active' => true,
                'level_requirement' => 5,
            ]);
        }
    }
}
```

---

## PHASE 6 — API Hardening

### Step 6.1: Install Laravel Sanctum (if not already)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Step 6.2: Create API Routes

File: `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    UnitController,
    TeamController,
    BattleController,
    ScanController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Units
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/units/{unit}', [UnitController::class, 'show']);

    // Teams
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::put('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);

    // Battles
    Route::get('/battles', [BattleController::class, 'index']);
    Route::post('/battles', [BattleController::class, 'store']);
    Route::get('/battles/{match}', [BattleController::class, 'show']);

    // Scans (with stricter rate limit)
    Route::post('/scan', [ScanController::class, 'store'])->middleware('throttle:10,1');
});
```

### Step 6.3: Create API Controllers

File: `app/Http/Controllers/Api/UnitController.php`

```php
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
            abort(403, 'This unit does not belong to you.');
        }

        return new UnitResource($unit->load('sector'));
    }
}
```

### Step 6.4: Create API Resources

File: `app/Http/Resources/UnitResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rarity' => $this->rarity,
            'tier' => $this->tier,
            'evolution_level' => $this->evolution_level,
            'source' => $this->source,
            'stats' => [
                'hp' => $this->hp,
                'attack' => $this->attack,
                'defense' => $this->defense,
                'speed' => $this->speed,
            ],
            'passive_ability' => $this->passive_ability,
            'sector' => [
                'id' => $this->sector->id,
                'name' => $this->sector->name,
                'slug' => $this->sector->slug,
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Step 6.5: Add API Tests

File: `tests/Feature/Api/UnitApiTest.php`

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\SummonedUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_their_units(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $unit = SummonedUnit::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/units');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'rarity', 'stats']
                ]
            ]);
    }

    public function test_user_cannot_view_another_users_unit(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);

        $unit = SummonedUnit::factory()->create(['user_id' => $user2->id]);

        $response = $this->getJson("/api/units/{$unit->id}");

        $response->assertStatus(403);
    }
}
```

### Step 6.6: Document API

Create `docs/API.md`:

```markdown
# ScanWarden API Documentation

## Authentication

All API endpoints require authentication using Laravel Sanctum tokens.

```bash
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}
```

Returns:
```json
{
  "token": "1|abc123..."
}
```

Use this token in the Authorization header:
```
Authorization: Bearer 1|abc123...
```

## Rate Limits

- General API: 60 requests per minute
- Scan endpoint: 10 requests per minute

## Endpoints

### Units

**GET /api/units**
Returns paginated list of authenticated user's units.

**GET /api/units/{id}**
Returns details of a specific unit (must be owned by user).

### Teams

**GET /api/teams**
Returns list of user's teams.

**POST /api/teams**
Create a new team.

### Battles

**GET /api/battles**
Returns battle history.

**POST /api/battles**
Queue a new battle.

### Scans

**POST /api/scan**
Perform a UPC scan (rate limited to 10/minute).
```

---

## Summary & Next Steps

You now have complete implementation details for all 6 phases:

1. ✅ **Phase 1**: Starter selection flow (Controller, middleware, views, tests)
2. ✅ **Phase 2**: Rarity tiers with probability-based summoning
3. ✅ **Phase 3**: Enhanced team management UI
4. ✅ **Phase 4**: Scan history with filtering
5. ✅ **Phase 5**: Essence transmuter system
6. ✅ **Phase 6**: Full API with resources, auth, rate limiting, and tests

### Implementation Order

1. Complete Phase 1 first (starter selection is foundational)
2. Then Phase 2 (rarity system enhances all summoning)
3. Phase 3-4 can be done in parallel (UI improvements)
4. Phase 5 requires Phases 1-2 complete
5. Phase 6 last (API layer on top of everything)

### Testing Strategy

Run tests after each phase:
```bash
php artisan test
```

### Database Commands

```bash
# After creating new migrations
php artisan migrate

# Seed transmutation recipes
php artisan db:seed --class=TransmutationRecipeSeeder
```

Good luck with implementation!
