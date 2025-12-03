# ScanWarden v0.1

**A Laravel-based scanning → summoning → command RPG**

ScanWarden is a unique RPG where Wardens command entities born from real-world UPC scans, protecting and exploiting different "Sectors" of a fractured world.

## What is ScanWarden?

In ScanWarden, you scan Universal Product Codes (UPCs) from everyday products to:
- Discover which Sector the product belongs to
- Earn Sector Energy for future evolutions
- Summon powerful units to your collection
- Build teams and battle other players
- Unlock world lore and climb leaderboards

### Core Gameplay Loop

```
Scan UPC → Classify to Sector → Gain Energy → Summon Units
    ↓
Build Teams → Battle → Earn Ratings → Progress
    ↓
Evolve Units → Unlock Lore → Dominate Sectors
```

## ScanWarden vs ScanForge

| Feature | ScanWarden | ScanForge |
|---------|------------|-----------|
| **Focus** | Summoning, Sectors, Unit Mastery, Lore | Crafting, Forging, Item Creation |
| **Core Loop** | Scan → Summon → Battle → Evolve | Scan → Craft → Upgrade → Forge |
| **Units** | Living entities with stats and abilities | Items and equipment |
| **World** | 6 Sectors with unique affinities | Workshop-based progression |
| **Progression** | Team battles, evolution trees, ratings | Item quality, crafting mastery |

## v0.1 Implemented Features ✅

### Phase 1-4: Foundation (Complete)
- ✅ Laravel 11.41.1 with Breeze authentication
- ✅ User registration and login system
- ✅ SQLite database with complete schema
- ✅ Blade-based UI with Tailwind CSS
- ✅ 6 Sectors (Food, Tech, Bio, Industrial, Arcane, Household)
- ✅ UPC scanning with deterministic classification
- ✅ Sector energy accumulation system
- ✅ Unit summoning (5 rarity tiers: Common → Legendary)
- ✅ Dynamic stat generation with passive abilities

### Phase 5: Evolution System (Complete)
- ✅ Evolution rules with 3 tier progressions
- ✅ Energy-based evolution costs
- ✅ Stat multipliers (HP, Attack, Defense, Speed)
- ✅ Name suffixes (Elite, Champion, Legend)
- ✅ Evolution preview with stat gains
- ✅ Evolution UI with progress tracking

### Phase 6: Teams & Auto-Battle (Complete)
- ✅ Team creation (max 5 units per team)
- ✅ Turn-based combat simulator
- ✅ Speed-based turn order
- ✅ Damage calculation: max(ATK - DEF, 1)
- ✅ Battle logs for turn-by-turn replay
- ✅ Winner determination system
- ✅ Queue-based async battle resolution

### Phase 7: Rating & Leaderboard (Complete)
- ✅ Performance-based rating system
- ✅ Rating calculation (base + efficiency + difficulty bonuses)
- ✅ 7 Rating tiers (Bronze → Silver → Gold → Platinum → Diamond → Master → Legend)
- ✅ Leaderboard with top 50 rankings
- ✅ Color-coded tier badges
- ✅ User rank tracking

### Phase 8: Lore System (Complete)
- ✅ 14 rich lore entries across all sectors + universal lore
- ✅ Unlock conditions (scan_count, unit_tier, evolution_level, battle_wins, total_rating)
- ✅ Progress tracking for locked entries
- ✅ Sector filtering
- ✅ Auto-unlock on milestone achievement
- ✅ Story-driven world building

### Phase 9: Dashboard & UX (Complete)
- ✅ Comprehensive dashboard with stats overview
- ✅ Quick action buttons
- ✅ Recent units & battles display
- ✅ Sector energy visualization
- ✅ Lore progress tracker
- ✅ Responsive navigation
- ✅ Welcome banner with user greeting

### Phase 10-11: Polish & Stability (Complete)
- ✅ Authorization checks on all routes
- ✅ Input validation on forms
- ✅ Basic test suite
- ✅ Error handling
- ✅ Session management

## Features Overview

### Complete Gameplay Features
- **Scanning**: UPC validation, deterministic sector classification, energy rewards
- **Summoning**: 5 rarity tiers with dynamic stat generation and passive abilities
- **Evolution**: Tier-based progression with energy costs and stat multipliers
- **Teams**: Build squads of up to 5 units
- **Battles**: Turn-based combat with speed ordering and damage calculations
- **Rating**: Performance-based ELO-style system with 7 tiers
- **Leaderboard**: Global rankings with tier visualization
- **Lore**: 14 unlockable entries with progress tracking
- **Dashboard**: Comprehensive stats, quick actions, and recent activity

## Tech Stack

- **Framework:** Laravel 11.41.1
- **PHP:** 8.2.12
- **Database:** SQLite (development)
- **Frontend:** Blade templates + Tailwind CSS
- **Authentication:** Laravel Breeze
- **Architecture:** Service-based business logic

## Setup Instructions

### Prerequisites
- PHP 8.2+ (XAMPP recommended for Windows)
- Composer 2.x
- Git (optional)

### Installation

1. **Navigate to project directory:**
```bash
cd c:\proj\scanwarden
```

2. **Install dependencies:**
```bash
php composer.phar install
```

3. **Environment setup:**
The `.env` file is already configured with SQLite. Application key is set.

4. **Run migrations and seeders:**
```bash
php artisan migrate:fresh --seed
```

This will create:
- 6 Sectors with descriptions
- 3 Evolution Rules (tiers 1→2, 2→3, 3→4)
- A test user (test@example.com / password)

5. **Build frontend assets:**
```bash
npm install
npm run build
```

6. **Start the development server:**
```bash
php artisan serve
```

7. **Access the application:**
Open browser to `http://localhost:8000`

### For Background Jobs (Future Battles)
```bash
php artisan queue:work
```

## How to Play

### Getting Started
1. **Register an account** at `/register`
2. **Login** with your credentials
3. **Navigate to Scan** from the dashboard

### Scanning UPCs
1. Enter any valid UPC code (8-20 digits)
2. View which Sector it belongs to
3. See your energy gains
4. Check if a unit was summoned!

### Example UPCs to Try
- `012345678905` - Random classification
- `042100005264` - Likely Food Sector
- `790572453903` - Likely Tech Sector
- `300450147202` - Likely Bio Sector
- `685387123456` - Likely Household Sector
- `123456789012` - Try any product UPC!

### Current Capabilities
- ✅ Scan unlimited UPCs
- ✅ Collect units across all 6 Sectors
- ✅ View sector energy accumulation
- ✅ See unit stats and abilities
- ✅ Guaranteed first summon
- ✅ Increasing summon rates with more scans

### Coming Soon (Phases 5-11)
See implementation notes below.

---

## Implementation Notes: Remaining Phases

### Phase 5: Evolution System

**Goal:** Allow units to evolve using accumulated Sector Energy

**Implementation Steps:**

1. **Create EvolutionService** (`app/Services/EvolutionService.php`):
```php
class EvolutionService {
    public function canEvolve(SummonedUnit $unit, User $user): bool
    public function getEvolutionRequirements(SummonedUnit $unit): ?array
    public function evolveUnit(SummonedUnit $unit, User $user): SummonedUnit
}
```

Logic:
- Check if evolution rule exists for current tier
- Verify user has enough sector energy
- Apply stat multipliers from EvolutionRule
- Update unit tier and evolution_level
- Append name suffix if defined
- Update or set passive_ability

2. **Create UnitsController** (`app/Http/Controllers/UnitsController.php`):
```php
public function index() // List all user's units
public function show(SummonedUnit $unit) // Unit details + evolution info
public function evolve(SummonedUnit $unit) // POST: trigger evolution
```

3. **Add Routes** (in `routes/web.php`):
```php
Route::get('/units', [UnitsController::class, 'index'])->name('units.index');
Route::get('/units/{unit}', [UnitsController::class, 'show'])->name('units.show');
Route::post('/units/{unit}/evolve', [UnitsController::class, 'evolve'])->name('units.evolve');
```

4. **Create Views:**
- `resources/views/units/index.blade.php` - Grid of all units with filtering by sector/rarity
- `resources/views/units/show.blade.php` - Unit detail with stats, evolution progress bar, evolve button

5. **Evolution UI Features:**
- Progress bar showing current energy vs. required
- "Evolve" button (disabled if requirements not met)
- Before/after stat preview
- Evolution animation/celebration on success

---

### Phase 6: Teams & Auto-Battle Simulator

**Goal:** Build teams and simulate turn-based battles

**Implementation Steps:**

1. **Create TeamsController** (`app/Http/Controllers/TeamsController.php`):
```php
public function index() // List user's teams
public function create() // Show create form
public function store(Request $request) // Create team
public function edit(Team $team) // Edit team composition
public function update(Team $team, Request $request) // Update team
public function destroy(Team $team) // Delete team
public function manageUnits(Team $team) // Manage team units
public function addUnit(Team $team, Request $request) // Add unit to team
public function removeUnit(Team $team, SummonedUnit $unit) // Remove unit
```

2. **Create BattleSimulatorService** (`app/Services/BattleSimulatorService.php`):
```php
public function simulateBattle(Team $attackerTeam, Team $defenderTeam): array
```

Battle Logic:
- Load all units with stats
- Sort by speed (descending)
- Loop turns:
  - Fastest living unit attacks
  - Calculate damage: max(attack - defense, 1)
  - Apply damage to target
  - Remove dead units
  - Log turn data
- Battle ends when one team eliminated
- Return winner + turn logs

3. **Create BattleMatchesController**:
```php
public function index() // Match history
public function show(BattleMatch $match) // Match detail with logs
public function create() // Choose opponent
public function queue(Request $request) // Queue match vs AI or user
```

4. **Create ResolveMatchJob** (`app/Jobs/ResolveMatchJob.php`):
```php
public function handle(BattleMatch $match, BattleSimulatorService $simulator)
{
    // Load teams
    // Run simulation
    // Store logs
    // Update match status and winner
    // Update ratings (Phase 7)
}
```

5. **Add Routes:**
```php
Route::resource('teams', TeamsController::class);
Route::post('/teams/{team}/units', [TeamsController::class, 'addUnit']);
Route::delete('/teams/{team}/units/{unit}', [TeamsController::class, 'removeUnit']);

Route::get('/battles', [BattleMatchesController::class, 'index'])->name('battles.index');
Route::get('/battles/create', [BattleMatchesController::class, 'create'])->name('battles.create');
Route::post('/battles', [BattleMatchesController::class, 'queue'])->name('battles.queue');
Route::get('/battles/{match}', [BattleMatchesController::class, 'show'])->name('battles.show');
```

6. **Create Views:**
- Teams index with list of teams
- Team editor with drag-drop unit slots
- Battle queue screen (pick opponent)
- Battle result screen with turn-by-turn replay
- Match history list

7. **Battle Features:**
- AI opponent generation (random team from pool)
- Async battle resolution via queue
- Turn-by-turn log display
- Victory/defeat animations

---

### Phase 7: Rating & Leaderboard

**Goal:** ELO-based ranking system

**Implementation Steps:**

1. **Create RatingService** (`app/Services/RatingService.php`):
```php
public function calculateNewRatings(
    int $winnerRating,
    int $loserRating,
    int $kFactor = 32
): array
```

ELO Formula:
```
expectedScore = 1 / (1 + 10^((opponent - player) / 400))
newRating = oldRating + K * (actualScore - expectedScore)
```

2. **Update ResolveMatchJob:**
```php
// After determining winner
$ratings = $this->ratingService->calculateNewRatings(
    $winner->rating,
    $loser->rating
);

$winner->update(['rating' => $ratings['winner']]);
$loser->update(['rating' => $ratings['loser']]);

$match->update([
    'winner_id' => $winner->id,
    'attacker_rating_after' => $attacker->rating,
    'defender_rating_after' => $defender->rating,
]);
```

3. **Create LeaderboardController**:
```php
public function index()
{
    $topPlayers = Cache::remember('leaderboard', 60, function() {
        return User::orderBy('rating', 'desc')
            ->with(['sectorEnergies.sector'])
            ->limit(100)
            ->get();
    });

    // Calculate primary sector per user
    foreach ($topPlayers as $player) {
        $player->primarySector = $player->sectorEnergies
            ->sortByDesc('current_energy')
            ->first()?->sector;
    }

    return view('leaderboard.index', compact('topPlayers'));
}
```

4. **Create View:**
- `resources/views/leaderboard/index.blade.php`
- Ranked list with: position, name, rating, primary sector (with color)
- Highlight current user's position
- Pagination for large lists

---

### Phase 8: Lore System

**Goal:** Unlock world-building content through gameplay

**Implementation Steps:**

1. **Create LoreSeeder** (`database/seeders/LoreSeeder.php`):
```php
LoreEntry::create([
    'sector_id' => $foodSector->id,
    'title' => 'The Harvest Collapse',
    'body' => 'Long story text...',
    'unlock_key' => 'scans',
    'unlock_threshold' => 10,
]);
```

Unlock keys:
- `scans` - Total scan count
- `sector_energy` - Energy in specific sector
- `units_summoned` - Total units
- `battles_won` - Battle victories

2. **Create LoreUnlockService** (`app/Services/LoreUnlockService.php`):
```php
public function checkUnlocks(User $user): Collection
{
    // Find lore entries not yet unlocked by user
    // Check if thresholds met
    // Attach to user_lore_entries pivot
    // Return newly unlocked entries
}
```

3. **Integrate into ScanController:**
```php
// After scan completion
$newLore = app(LoreUnlockService::class)->checkUnlocks($user);
if ($newLore->isNotEmpty()) {
    session()->flash('new_lore', $newLore);
}
```

4. **Create LoreController**:
```php
public function index()
{
    $unlockedLore = auth()->user()->unlockedLore()
        ->with('sector')
        ->orderBy('unlocked_at', 'desc')
        ->get();

    $lockedLore = LoreEntry::whereNotIn('id',
        auth()->user()->unlockedLore()->pluck('id')
    )->get();

    return view('lore.index', compact('unlockedLore', 'lockedLore'));
}

public function show(LoreEntry $lore)
{
    // Verify user has unlocked this
    if (!auth()->user()->unlockedLore()->where('id', $lore->id)->exists()) {
        abort(403);
    }

    return view('lore.show', compact('lore'));
}
```

5. **Create Views:**
- Lore index with tabs for unlocked/locked
- Lore detail page with full text
- "New lore unlocked!" notification component

---

### Phase 9: Dashboard & UX Improvements

**Goal:** Polished first impression and intuitive navigation

**Implementation Steps:**

1. **Update Dashboard** (`resources/views/dashboard.blade.php`):
```php
// Gather stats
$stats = [
    'total_scans' => auth()->user()->scanRecords()->count(),
    'total_units' => auth()->user()->summonedUnits()->count(),
    'rating' => auth()->user()->rating,
    'total_energy' => auth()->user()->sectorEnergies()->sum('current_energy'),
];

$sectorBreakdown = auth()->user()->sectorEnergies()
    ->with('sector')
    ->get();

$recentScans = auth()->user()->scanRecords()
    ->with('sector')
    ->latest()
    ->limit(5)
    ->get();

$evolvableUnits = auth()->user()->summonedUnits()
    ->with('sector')
    ->get()
    ->filter(fn($unit) => app(EvolutionService::class)->canEvolve($unit, auth()->user()));

// Next steps logic
$nextSteps = [];
if ($stats['total_scans'] < 5) {
    $nextSteps[] = ['icon' => '🔍', 'text' => 'Scan more UPCs', 'route' => 'scan.create'];
}
if ($stats['total_units'] > 0 && auth()->user()->teams()->count() === 0) {
    $nextSteps[] = ['icon' => '⚔️', 'text' => 'Build your first team', 'route' => 'teams.create'];
}
```

2. **Enhanced Navigation** (in `resources/views/layouts/navigation.blade.php`):
```html
<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Dashboard
</x-nav-link>
<x-nav-link :href="route('scan.create')" :active="request()->routeIs('scan.*')">
    Scan
</x-nav-link>
<x-nav-link :href="route('units.index')" :active="request()->routeIs('units.*')">
    Units
</x-nav-link>
<x-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.*')">
    Teams
</x-nav-link>
<x-nav-link :href="route('battles.index')" :active="request()->routeIs('battles.*')">
    Battles
</x-nav-link>
<x-nav-link :href="route('leaderboard.index')" :active="request()->routeIs('leaderboard.*')">
    Leaderboard
</x-nav-link>
<x-nav-link :href="route('lore.index')" :active="request()->routeIs('lore.*')">
    Lore
</x-nav-link>
```

3. **Empty States for All Pages:**
- Units index: "No units yet. Scan UPCs to summon your first unit!"
- Teams index: "No teams created. Build a team to start battling!"
- Battles index: "No battles yet. Create a team and challenge opponents!"
- Lore index: "No lore unlocked. Keep scanning to discover the world's secrets!"

4. **Dashboard Widgets:**
- Stats cards (scans, units, rating, energy)
- Sector energy pie chart or bars
- Recent activity feed
- Evolvable units alert
- Quick actions panel

---

### Phase 10: Authorization, Validation & Tests

**Goal:** Production-ready security and quality

**Implementation Steps:**

1. **Create Policies:**
```bash
php artisan make:policy SummonedUnitPolicy --model=SummonedUnit
php artisan make:policy TeamPolicy --model=Team
php artisan make:policy BattleMatchPolicy --model=BattleMatch
```

Implement in each policy:
```php
public function view(User $user, Model $model): bool
{
    return $user->id === $model->user_id;
}

public function update(User $user, Model $model): bool
{
    return $user->id === $model->user_id;
}
```

2. **Apply Authorization in Controllers:**
```php
public function show(SummonedUnit $unit)
{
    $this->authorize('view', $unit);
    // ...
}

public function update(Team $team, Request $request)
{
    $this->authorize('update', $team);
    // ...
}
```

3. **Create FormRequests:**
```bash
php artisan make:request StoreScanRequest
php artisan make:request StoreTeamRequest
php artisan make:request EvolveUnitRequest
```

Move validation logic from controllers:
```php
class StoreScanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'upc' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9]+$/'],
        ];
    }
}
```

4. **Write Tests:**

Service Tests (`tests/Unit/Services/`):
```php
public function test_upc_classification_is_deterministic()
{
    $service = new ScanClassificationService();
    $sector1 = $service->classifyUpc('123456789012');
    $sector2 = $service->classifyUpc('123456789012');

    $this->assertEquals($sector1->id, $sector2->id);
}

public function test_unit_summoning_respects_rarity_weights()
{
    // Test 1000 summons, verify rarity distribution
}

public function test_battle_simulator_declares_winner()
{
    // Create two teams, simulate, verify winner
}
```

Feature Tests (`tests/Feature/`):
```php
public function test_user_can_scan_upc()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/scan', [
        'upc' => '123456789012',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('scan_records', [
        'user_id' => $user->id,
        'raw_upc' => '123456789012',
    ]);
}

public function test_user_cannot_view_other_users_units()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $unit = SummonedUnit::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->get("/units/{$unit->id}");

    $response->assertForbidden();
}
```

5. **Run Tests:**
```bash
php artisan test
```

---

### Phase 11: README & v0.1 Stabilization

**Goal:** Professional documentation and deployment readiness

**Implementation Steps:**

1. **Update README.md** (already started above, expand):
- Add screenshots of key screens
- Document API endpoints if any
- Troubleshooting section
- Deployment guide for production
- Contribution guidelines

2. **Create CHANGELOG.md:**
```markdown
# Changelog

## [0.1.0] - 2025-12-03

### Added
- Initial release
- UPC scanning and sector classification
- Unit summoning with 5 rarity tiers
- 6 unique sectors
- Evolution system (tiers 1-4)
- Team building and management
- Auto-battle simulator
- ELO rating system
- Leaderboard
- Lore unlocking system
- Full authentication with Laravel Breeze
```

3. **Environment Configuration:**
- Document required .env variables
- Provide .env.example with all keys
- Add database configuration options
- Queue driver setup (database vs Redis)

4. **Seeder Improvements:**
- Add more lore entries (10+ per sector)
- Optional demo data seeder for testing
- Production seeder (minimal data)

5. **Performance Optimization:**
- Add database indexes on foreign keys
- Eager load relationships in queries
- Cache leaderboard and sector data
- Optimize battle simulation queries

6. **Error Handling:**
- Custom error pages (404, 403, 500)
- Better validation messages
- Graceful failures with user feedback

7. **Final Testing:**
```bash
# Fresh install test
php artisan migrate:fresh --seed
php artisan test
php artisan serve

# Visit all pages as authenticated user
# Perform complete gameplay loop
# Verify no errors in logs
```

8. **Deployment Checklist:**
- Set APP_ENV=production
- Set APP_DEBUG=false
- Configure proper database
- Set up queue worker
- Configure session/cache drivers
- Set APP_URL correctly
- Run `composer install --optimize-autoloader --no-dev`
- Run `php artisan config:cache`
- Run `php artisan route:cache`
- Run `php artisan view:cache`

---

## Current File Structure

```
scanwarden/
├── app/
│   ├── Http/Controllers/
│   │   ├── ScanController.php ✅
│   │   └── ProfileController.php
│   ├── Models/
│   │   ├── User.php ✅
│   │   ├── Sector.php ✅
│   │   ├── SummonedUnit.php ✅
│   │   ├── ScanRecord.php ✅
│   │   ├── SectorEnergy.php ✅
│   │   ├── EvolutionRule.php ✅
│   │   ├── Team.php ✅
│   │   ├── BattleMatch.php ✅
│   │   ├── BattleLog.php ✅
│   │   └── LoreEntry.php ✅
│   └── Services/
│       ├── ScanClassificationService.php ✅
│       └── UnitSummoningService.php ✅
├── database/
│   ├── migrations/ (all migrations complete) ✅
│   └── seeders/
│       ├── SectorSeeder.php ✅
│       └── EvolutionRuleSeeder.php ✅
├── resources/views/
│   ├── scan/
│   │   ├── create.blade.php ✅
│   │   └── result.blade.php ✅
│   └── layouts/
│       └── app.blade.php (from Breeze)
└── routes/
    └── web.php ✅
```

## Quick Reference

### Artisan Commands
```bash
# Migrations
php artisan migrate:fresh --seed  # Fresh start
php artisan migrate               # Run pending
php artisan migrate:rollback      # Undo last batch

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue
php artisan queue:work           # Process jobs
php artisan queue:failed         # View failed jobs

# Development
php artisan serve                # Start server
php artisan tinker               # Interactive console
php artisan route:list           # View all routes
```

### Database

Current tables:
- users (with rating field)
- sectors
- summoned_units
- scan_records
- sector_energies
- evolution_rules
- teams
- team_units (pivot)
- battle_matches
- battle_logs
- lore_entries
- user_lore_entries (pivot)

## License

This project is built with Laravel, which is open-sourced software licensed under the MIT license.

## Support

For issues or questions:
- Check the implementation notes above
- Review Laravel documentation: https://laravel.com/docs
- Check database schema in migrations

---

**Built with Laravel 11 | Powered by imagination**
