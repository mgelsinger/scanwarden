# ScanWarden Implementation Guide - 6 Phases

This document provides complete implementation details for all 6 phases.

## Status

✅ **Phase 1 (Partial)**: Started
- ✅ Migration for `source` field created and run
- ✅ SummonedUnit model updated
- ✅ Starter config file created
- ✅ StarterController created
- ⏳ Middleware needed
- ⏳ Routes needed
- ⏳ Blade views needed
- ⏳ Tests needed

---

## PHASE 1 — Starter Selection Flow (CONTINUATION)

### Step 1.4: Implement Middleware

File: `app/Http/Middleware/EnsureStarterSelected.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStarterSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not authenticated
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip if already on starter selection page
        if ($request->routeIs('starter.*')) {
            return $next($request);
        }

        // Redirect to starter selection if user has no units
        if (auth()->user()->summonedUnits()->count() === 0) {
            return redirect()->route('starter.index')
                ->with('info', 'Please choose your starter unit to begin your journey!');
        }

        return $next($request);
    }
}
```

### Step 1.5: Register Middleware

In `bootstrap/app.php`, add to the middleware section:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'starter.selected' => \App\Http\Middleware\EnsureStarterSelected::class,
    ]);
})
```

### Step 1.6: Add Routes

In `routes/web.php`, add these routes BEFORE the authenticated routes group:

```php
// Starter selection routes (authenticated but before starter check)
Route::middleware('auth')->group(function () {
    Route::get('/starter', [StarterController::class, 'index'])->name('starter.index');
    Route::post('/starter', [StarterController::class, 'store'])->name('starter.store');
});
```

Then update the dashboard and main routes to use the middleware:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'starter.selected'])
    ->name('dashboard');

Route::middleware(['auth', 'starter.selected'])->group(function () {
    // All existing routes (scan, units, teams, battles, etc.)
});
```

### Step 1.7: Create Blade Views

Create directory: `resources/views/starter/`

File: `resources/views/starter/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Choose Your Starter') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Message -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome, New Warden!</h3>
                <p class="text-gray-700">
                    Before you begin your journey through the fractured world of ScanWarden, you must choose a starter unit.
                    Each starter represents a different sector and playstyle. Choose wisely - your starter will be your first ally in battles to come.
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Starter Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($starters as $starter)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 border-2 border-gray-200 hover:border-indigo-500">
                        <!-- Header with Sector Color -->
                        <div class="p-4" style="background: linear-gradient(135deg, {{ $starter['sector']->color ?? '#6B7280' }} 0%, {{ $starter['sector']->color ?? '#6B7280' }}99 100%);">
                            <h3 class="text-2xl font-bold text-white mb-1">{{ $starter['name'] }}</h3>
                            <p class="text-white text-sm opacity-90">{{ $starter['sector']->name ?? 'Unknown Sector' }}</p>
                            <span class="inline-block mt-2 px-3 py-1 bg-white bg-opacity-30 rounded-full text-white text-xs font-semibold uppercase">
                                {{ ucfirst($starter['rarity']) }}
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="p-4 bg-gray-50">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="flex items-center">
                                    <span class="font-semibold text-red-600">HP:</span>
                                    <span class="ml-2">{{ $starter['hp'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-orange-600">ATK:</span>
                                    <span class="ml-2">{{ $starter['attack'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-blue-600">DEF:</span>
                                    <span class="ml-2">{{ $starter['defense'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-semibold text-green-600">SPD:</span>
                                    <span class="ml-2">{{ $starter['speed'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description and Ability -->
                        <div class="p-4">
                            <p class="text-gray-700 text-sm mb-3">{{ $starter['description'] }}</p>

                            <div class="bg-purple-50 border border-purple-200 rounded p-3 mb-4">
                                <p class="text-xs font-semibold text-purple-900 mb-1">PASSIVE ABILITY</p>
                                <p class="text-sm text-purple-800">{{ $starter['passive_ability'] }}</p>
                            </div>

                            <div class="bg-gray-100 border border-gray-300 rounded p-3 mb-4">
                                <p class="text-xs italic text-gray-600">{{ $starter['lore'] }}</p>
                            </div>
                        </div>

                        <!-- Choose Button -->
                        <div class="p-4 bg-gray-50 border-t">
                            <form method="POST" action="{{ route('starter.store') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="starter_key" value="{{ $starter['key'] }}">
                                <button
                                    type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200"
                                    onclick="return confirm('Are you sure you want to choose {{ $starter['name'] }}? This choice is permanent!');"
                                >
                                    Choose {{ $starter['name'] }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
```

### Step 1.8: Create Tests

File: `tests/Feature/StarterSelectionTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_to_starter_selection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('starter.index'));
    }

    public function test_starter_selection_page_displays_starters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('starter.index'));

        $response->assertOk();
        $response->assertSee('Choose Your Starter');
        $response->assertSee('Aegis Guardian');
        $response->assertSee('Spark Striker');
    }

    public function test_user_can_select_a_starter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('summoned_units', [
            'user_id' => $user->id,
            'name' => 'Aegis Guardian',
            'source' => 'starter',
        ]);
    }

    public function test_user_cannot_select_starter_twice(): void
    {
        $user = User::factory()->create();

        // Select first starter
        $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        // Try to select another
        $response = $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'spark_striker',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals(1, $user->summonedUnits()->count());
    }

    public function test_user_with_starter_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}
```

---

## PHASE 2 — Unit Rarity Tiers + Summoning Integration

### Step 2.1: Create Rarity Config

File: `config/rarities.php`

```php
<?php

return [
    'tiers' => [
        'common' => [
            'name' => 'Common',
            'color' => '#9CA3AF', // gray
            'probability' => 50, // out of 100
            'stat_multiplier' => 1.0,
        ],
        'uncommon' => [
            'name' => 'Uncommon',
            'color' => '#10B981', // green
            'probability' => 30,
            'stat_multiplier' => 1.2,
        ],
        'rare' => [
            'name' => 'Rare',
            'color' => '#3B82F6', // blue
            'probability' => 15,
            'stat_multiplier' => 1.5,
        ],
        'epic' => [
            'name' => 'Epic',
            'color' => '#8B5CF6', // purple
            'probability' => 4,
            'stat_multiplier' => 1.8,
        ],
        'legendary' => [
            'name' => 'Legendary',
            'color' => '#F59E0B', // amber/gold
            'probability' => 1,
            'stat_multiplier' => 2.2,
        ],
    ],
];
```

### Step 2.2: Update UnitSummoningService

In `app/Services/UnitSummoningService.php`, add rarity selection logic:

```php
public function determineRarity(): string
{
    $rarities = config('rarities.tiers');
    $rand = rand(1, 100);

    $cumulativeProbability = 0;
    foreach ($rarities as $key => $rarity) {
        $cumulativeProbability += $rarity['probability'];
        if ($rand <= $cumulativeProbability) {
            return $key;
        }
    }

    return 'common'; // fallback
}

public function applyRarityMultiplier(array $baseStats, string $rarity): array
{
    $multiplier = config("rarities.tiers.{$rarity}.stat_multiplier", 1.0);

    return [
        'hp' => (int) round($baseStats['hp'] * $multiplier),
        'attack' => (int) round($baseStats['attack'] * $multiplier),
        'defense' => (int) round($baseStats['defense'] * $multiplier),
        'speed' => (int) round($baseStats['speed'] * $multiplier),
    ];
}
```

Then update the `summonUnit()` method to use these:

```php
// After determining base stats...
$rarity = $this->determineRarity();
$stats = $this->applyRarityMultiplier($baseStats, $rarity);

$unit = SummonedUnit::create([
    // ...
    'rarity' => $rarity,
    'hp' => $stats['hp'],
    'attack' => $stats['attack'],
    'defense' => $stats['defense'],
    'speed' => $stats['speed'],
    // ...
]);
```

### Step 2.3: Update Unit Display Views

In all unit list views (units/index.blade.php, teams/show.blade.php, etc.), add rarity badges:

```blade
<span class="inline-block px-2 py-1 text-xs font-semibold rounded"
      style="background-color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }}; color: white;">
    {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
</span>
```

### Step 2.4: Create Rarity Tests

File: `tests/Unit/Services/RarityDistributionTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\Services\UnitSummoningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RarityDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rarity_distribution_is_reasonable(): void
    {
        $service = new UnitSummoningService();
        $results = [];

        // Simulate 1000 summons
        for ($i = 0; $i < 1000; $i++) {
            $rarity = $service->determineRarity();
            $results[$rarity] = ($results[$rarity] ?? 0) + 1;
        }

        // Common should be most frequent
        $this->assertGreaterThan(400, $results['common'] ?? 0);

        // Legendary should be rarest
        $this->assertLessThan(50, $results['legendary'] ?? 0);
    }

    public function test_rarity_multiplier_increases_stats(): void
    {
        $service = new UnitSummoningService();
        $baseStats = ['hp' => 100, 'attack' => 50, 'defense' => 50, 'speed' => 50];

        $commonStats = $service->applyRarityMultiplier($baseStats, 'common');
        $legendaryStats = $service->applyRarityMultiplier($baseStats, 'legendary');

        $this->assertEquals(100, $commonStats['hp']);
        $this->assertGreaterThan($commonStats['hp'], $legendaryStats['hp']);
    }
}
```

---

## PHASE 3 — Parties/Teams UI Enhancement

**Note**: Teams system already exists. This phase enhances the UI.

### Step 3.1: Enhance Team Builder View

Update `resources/views/teams/edit.blade.php` or create if missing:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Team Builder') }}
            </h2>
            <a href="{{ route('teams.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Teams
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Team Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4">{{ $team->name }}</h3>
                <p class="text-gray-600">
                    {{ $team->units->count() }} / 5 units
                </p>
            </div>

            <!-- Current Team Units -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h4 class="font-bold text-lg mb-4">Current Team</h4>
                @if($team->units->isEmpty())
                    <p class="text-gray-500 italic">No units in team yet. Add some below!</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        @foreach($team->units as $unit)
                            <div class="border rounded-lg p-4">
                                <h5 class="font-semibold">{{ $unit->name }}</h5>
                                <span class="text-xs" style="color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }}">
                                    {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
                                </span>
                                <div class="text-sm mt-2">
                                    <div>HP: {{ $unit->hp }}</div>
                                    <div>ATK: {{ $unit->attack }}</div>
                                </div>
                                <form method="POST" action="{{ route('teams.removeUnit', [$team, $unit]) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 text-xs hover:text-red-800">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Available Units -->
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="font-bold text-lg mb-4">Available Units</h4>
                @if($availableUnits->isEmpty())
                    <p class="text-gray-500 italic">All your units are assigned!</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($availableUnits as $unit)
                            <div class="border rounded-lg p-4">
                                <h5 class="font-semibold">{{ $unit->name }}</h5>
                                <span class="text-xs" style="color: {{ config('rarities.tiers.' . $unit->rarity . '.color') }}">
                                    {{ config('rarities.tiers.' . $unit->rarity . '.name') }}
                                </span>
                                <div class="text-sm mt-2">
                                    <div>HP: {{ $unit->hp }}</div>
                                    <div>ATK: {{ $unit->attack }}</div>
                                </div>
                                @if($team->units->count() < 5)
                                    <form method="POST" action="{{ route('teams.addUnit', $team) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                        <button class="bg-green-600 text-white text-xs px-3 py-1 rounded hover:bg-green-700">
                                            Add to Team
                                        </button>
                                    </form>
                                @else
                                    <p class="text-xs text-gray-500 mt-2">Team full</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

[Continue with remaining phases in next message due to length...]

