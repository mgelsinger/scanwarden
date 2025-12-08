# ScanWarden - AI Context Document

## Project Overview

**ScanWarden** is a gamified UPC barcode scanning web application built with Laravel 11. Users scan real-world product barcodes to collect battle units, earn resources, and build teams. The game uses a sector-based classification system that categorizes scanned products into one of six fantasy sectors.

**Status**: All 6 implementation phases complete and tested (31/31 tests passing)
**Tech Stack**: Laravel 11, PHP 8.3, MySQL/SQLite, Tailwind CSS, Alpine.js, Laravel Breeze
**Repository**: https://github.com/mgelsinger/scanwarden

---

## Completed Implementation Phases

### Phase 1: Starter Selection Flow ✅
Users must choose a starter unit before accessing the main game.

**Components:**
- `app/Http/Controllers/StarterController.php` - Handles starter selection
- `app/Http/Middleware/EnsureStarterSelected.php` - Middleware to enforce starter selection
- `resources/views/starter/index.blade.php` - Starter selection UI
- `config/starters.php` - Defines 6 starter units (one per sector)
- `tests/Feature/StarterSelectionTest.php` - 5/5 tests passing

**Database:**
- Added `source` field to `summoned_units` table (values: 'starter', 'scan', 'transmutation')

**Flow:**
1. New users are redirected to starter selection
2. Choose from 6 rare-tier starter units
3. Receive 50 initial sector energy
4. Gain access to main application

---

### Phase 2: Rarity System ✅
Units are categorized into 5 rarity tiers with stat multipliers.

**Components:**
- `config/rarities.php` - Defines 5 rarity tiers and stat multipliers
- `app/Services/UnitSummoningService.php` - Implements rarity logic
- `tests/Unit/Services/RarityDistributionTest.php` - 4/4 tests passing

**Rarity Tiers:**
```php
'common'    => ['chance' => 0.50, 'multiplier' => 1.0],
'uncommon'  => ['chance' => 0.25, 'multiplier' => 1.2],
'rare'      => ['chance' => 0.15, 'multiplier' => 1.5],
'epic'      => ['chance' => 0.08, 'multiplier' => 2.0],
'legendary' => ['chance' => 0.02, 'multiplier' => 3.0],
```

**Features:**
- Rarity badges displayed on all unit cards
- Color-coded rarity system (gray → purple)
- Progressive stat multipliers
- Rarity affects passive ability generation

---

### Phase 3: Team Builder UI Enhancement ✅
Users can create and manage teams of up to 5 units.

**Components:**
- `app/Http/Controllers/TeamsController.php` - Team CRUD operations
- `app/Policies/TeamPolicy.php` - Authorization for team operations
- `resources/views/teams/edit.blade.php` - Team management UI
- `resources/views/teams/show.blade.php` - Team details view
- `tests/Feature/TeamBuilderTest.php` - 7/7 tests passing

**Features:**
- Create multiple teams
- Add/remove units from teams
- Position management (1-5)
- Unit availability display
- Authorization checks (users can only manage their own teams)

**Database:**
- `teams` table
- `team_units` pivot table with position field

---

### Phase 4: Scan History Page ✅
Track and review all past scans with filtering and pagination.

**Components:**
- `app/Http/Controllers/ScanHistoryController.php` - History display
- `resources/views/scan-history/index.blade.php` - History UI with filters
- `tests/Feature/ScanHistoryTest.php` - 6/6 tests passing

**Features:**
- Paginated scan history (10 per page)
- Filter by sector
- Display rewards (energy, units summoned)
- Timestamp for each scan
- Shows UPC code, sector classification, and rewards

---

### Phase 5: Essence Transmuter System ✅
Economy/crafting system for converting resources into guaranteed rewards.

**Components:**
- `app/Models/UserEssence.php` - User essence inventory
- `app/Models/TransmutationRecipe.php` - Recipe definitions
- `app/Models/TransmutationHistory.php` - Transmutation audit log
- `app/Services/EssenceTransmuterService.php` - Transmutation logic
- `app/Http/Controllers/TransmuterController.php` - Transmuter UI controller
- `resources/views/transmuter/index.blade.php` - Transmuter UI
- `database/seeders/TransmutationRecipeSeeder.php` - Seeds 13 recipes

**Database Tables:**
```sql
user_essence (id, user_id, sector_id, amount, type, timestamps)
transmutation_recipes (id, name, description, required_inputs, outputs, sector_id, is_active, level_requirement)
transmutation_history (id, user_id, recipe_id, inputs_consumed, outputs_received, timestamps)
```

**Recipe System:**
- **Essence Consolidation**: 100 generic essence → 50 sector essence
- **Sector Rare Summons**: 200 sector energy → guaranteed Rare unit (6 recipes, one per sector)
- **Sector Epic Summons**: 500 sector energy → guaranteed Epic unit (6 recipes, one per sector)

**Features:**
- Affordability checks before transmutation
- Transaction safety (database transactions)
- JSON-based recipe inputs/outputs
- Support for essence, sector energy, and unit summons

---

### Phase 6: REST API Layer ✅
Full REST API with Sanctum authentication for mobile/external apps.

**Components:**
- Laravel Sanctum installed and configured
- `app/Http/Controllers/Api/AuthController.php` - Login/register/logout
- `app/Http/Controllers/Api/UnitController.php` - Unit API endpoints
- `app/Http/Controllers/Api/TeamController.php` - Team API endpoints
- `app/Http/Controllers/Api/ScanController.php` - Scan API endpoint
- `app/Http/Resources/UnitResource.php` - Unit JSON serialization
- `app/Http/Resources/TeamResource.php` - Team JSON serialization
- `routes/api.php` - API routes
- `tests/Feature/Api/AuthApiTest.php` - 5/5 tests passing
- `tests/Feature/Api/UnitApiTest.php` - 4/4 tests passing

**API Endpoints:**

**Public:**
- `POST /api/register` - Create account
- `POST /api/login` - Get authentication token

**Protected (requires Bearer token):**
- `POST /api/logout` - Invalidate token
- `GET /api/user` - Get current user
- `GET /api/units` - List user's units (paginated)
- `GET /api/units/{unit}` - Get unit details
- `GET /api/teams` - List user's teams
- `POST /api/teams` - Create team
- `GET /api/teams/{team}` - Get team with units
- `PUT /api/teams/{team}` - Update team
- `DELETE /api/teams/{team}` - Delete team
- `POST /api/teams/{team}/units` - Add unit to team
- `DELETE /api/teams/{team}/units/{unit}` - Remove unit from team
- `POST /api/scan` - Scan UPC (rate limit: 10/min)
- `GET /api/scans` - Scan history

**Security:**
- Token-based authentication via Sanctum
- Rate limiting: 60 requests/min (general), 10 requests/min (scan)
- Authorization checks on all endpoints
- Input validation

---

## Core Game Mechanics

### Sector Classification System

**Six Sectors:**
1. **Food Sector** (Orange #FF6B35) - Consumables, food products
2. **Tech Sector** (Blue #004E98) - Electronics, gadgets
3. **Bio Sector** (Teal #2A9D8F) - Organic, medicines, natural products
4. **Industrial Sector** (Gray #6C757D) - Tools, materials, manufactured goods
5. **Arcane Sector** (Purple #8B5CF6) - Books, games, mysterious items
6. **Household Sector** (Tan #F4A261) - Common household items

**Classification Logic** (`ScanClassificationService`):
- Uses UPC prefix patterns to classify products
- Falls back to modulo-based distribution if no pattern match
- Deterministic based on UPC (same UPC always gives same sector)

### Energy System

**Sector Energy:**
- Each scan grants 10-30 energy points to the classified sector
- Energy stored per user per sector (`sector_energies` table)
- Used for:
  - Transmutation recipes (guaranteed unit summons)
  - Future features (not yet implemented)

### Unit Summoning

**Summon Triggers:**
- First scan: Guaranteed unit summon
- Subsequent scans: 25% chance to summon (configurable)

**Unit Generation:**
- Deterministic based on UPC seed + user ID + timestamp
- Random name generation from sector-themed word lists
- Random base stats (40-80 HP, 20-40 Attack, 15-35 Defense, 15-40 Speed)
- Rarity applied via weighted random selection
- Stat multipliers applied based on rarity
- Passive abilities generated based on sector and rarity

**Unit Stats:**
- HP (Health Points)
- Attack
- Defense
- Speed
- Passive Ability (text description)
- Rarity (common/uncommon/rare/epic/legendary)
- Tier (1-5, for evolution - not yet implemented)
- Evolution Level (0-3, not yet implemented)

---

## Database Schema

### Core Tables

**users**
- Standard Laravel auth fields
- `rating` (integer) - PvP rating

**sectors**
- id, name, slug, description, color
- 6 sectors seeded

**summoned_units**
- id, user_id, sector_id
- name, rarity, tier, evolution_level
- hp, attack, defense, speed
- passive_ability (text)
- source (enum: 'starter', 'scan', 'transmutation')
- timestamps

**teams**
- id, user_id, name
- timestamps

**team_units** (pivot)
- team_id, summoned_unit_id, position (1-5)

**scan_records**
- id, user_id, sector_id
- raw_upc (string)
- rewards (JSON: energy_gained, sector_name, should_summon, summoned_unit)
- timestamps

**sector_energies**
- id, user_id, sector_id
- current_energy (integer)
- timestamps

**user_essence**
- id, user_id, sector_id (nullable)
- amount, type (enum: 'generic', 'sector')
- timestamps

**transmutation_recipes**
- id, name, description
- required_inputs (JSON array)
- outputs (JSON array)
- sector_id (nullable), is_active, level_requirement
- timestamps

**transmutation_history**
- id, user_id, recipe_id
- inputs_consumed (JSON), outputs_received (JSON)
- timestamps

**personal_access_tokens** (Sanctum)
- Standard Sanctum token table

### Additional Tables (Seeded but not in Phase 1-6)
- evolution_rules
- battle_matches
- battle_logs
- lore_entries
- user_lore_entries

---

## File Structure

### Controllers
```
app/Http/Controllers/
├── Api/
│   ├── AuthController.php
│   ├── ScanController.php
│   ├── TeamController.php
│   └── UnitController.php
├── BattleMatchesController.php
├── LeaderboardController.php
├── LoreController.php
├── ProfileController.php
├── ScanController.php
├── ScanHistoryController.php
├── StarterController.php
├── TeamsController.php
├── TransmuterController.php
└── UnitController.php
```

### Services
```
app/Services/
├── EssenceTransmuterService.php
├── LoreService.php
├── ScanClassificationService.php
└── UnitSummoningService.php
```

### Models
```
app/Models/
├── BattleLog.php
├── BattleMatch.php
├── EvolutionRule.php
├── LoreEntry.php
├── ScanRecord.php
├── Sector.php
├── SectorEnergy.php
├── SummonedUnit.php
├── Team.php
├── TransmutationHistory.php
├── TransmutationRecipe.php
├── User.php
├── UserEssence.php
└── UserLoreEntry.php
```

### Views
```
resources/views/
├── layouts/
│   ├── app.blade.php
│   ├── guest.blade.php
│   └── navigation.blade.php
├── scan/
│   ├── create.blade.php (UPC entry form)
│   └── result.blade.php (Scan results display)
├── scan-history/
│   └── index.blade.php
├── starter/
│   └── index.blade.php
├── teams/
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── transmuter/
│   └── index.blade.php
├── units/
│   ├── index.blade.php
│   └── show.blade.php
├── battles/ (exists but not Phase 1-6)
├── leaderboard/ (exists but not Phase 1-6)
└── lore/ (exists but not Phase 1-6)
```

### Configuration
```
config/
├── rarities.php (Rarity tiers and multipliers)
├── sanctum.php (API authentication)
└── starters.php (6 starter unit templates)
```

---

## Navigation Menu

Current menu items (in order):
1. Dashboard
2. Scan (UPC entry)
3. Units (collection)
4. Teams (team builder)
5. Transmuter (crafting)
6. History (scan history)
7. Battles (PvP - exists but not implemented in phases)
8. Leaderboard (rankings - exists but not implemented in phases)
9. Lore (story - exists but not implemented in phases)

---

## Testing Status

**Total: 31/31 tests passing**

- Phase 1: 5/5 (StarterSelectionTest)
- Phase 2: 4/4 (RarityDistributionTest)
- Phase 3: 7/7 (TeamBuilderTest)
- Phase 4: 6/6 (ScanHistoryTest)
- Phase 5: No tests (feature complete, no test requirement)
- Phase 6: 9/9 (AuthApiTest: 5, UnitApiTest: 4)

**Test Execution:**
```bash
php artisan test
```

---

## How to Run

### Development Server
```bash
php artisan serve
# Runs at http://127.0.0.1:8000
```

### Database Setup
```bash
php artisan migrate --seed
# Runs all migrations and seeds sectors, evolution rules, transmutation recipes
```

### Create Test User
```bash
php artisan tinker
>>> User::factory()->create(['email' => 'test@test.com', 'password' => bcrypt('password')])
```

---

## User Journey

### First-Time User
1. Register account at `/register`
2. Redirected to `/starter` - choose one of 6 starter units
3. Receive starter unit + 50 sector energy
4. Redirected to `/dashboard`
5. Can now access all features

### Core Gameplay Loop
1. **Scan UPCs** (`/scan`)
   - Enter any 8-20 digit UPC
   - Get classified to a sector
   - Earn 10-30 sector energy
   - 25% chance to summon a unit

2. **Collect Units** (`/units`)
   - View all summoned units
   - Filter by sector/rarity
   - See stats and abilities

3. **Build Teams** (`/teams`)
   - Create teams (up to 5 units)
   - Manage team composition
   - View team stats

4. **Use Transmuter** (`/transmuter`)
   - View available recipes
   - Check affordability
   - Craft guaranteed units using sector energy

5. **Review History** (`/scan-history`)
   - See all past scans
   - Filter by sector
   - Track progression

---

## Known Limitations & Future Features

### Not Yet Implemented (But Tables Exist)
- Evolution system (units can evolve through tiers)
- PvP battles (battle_matches, battle_logs tables exist)
- Leaderboard system
- Lore system (lore_entries, user_lore_entries)
- Essence generation from scans (system exists but essence not awarded on scan)

### Current Workarounds
- Essence must be manually granted via tinker for testing transmuter
- No actual barcode scanner integration (manual UPC entry)
- No image assets for units/sectors (text-based display)

---

## Environment Requirements

**PHP:** 8.3.27
**Laravel:** 11.x
**Database:** SQLite (dev) or MySQL (production)
**Node:** Not required (no build step, CDN-based frontend)

**Composer Dependencies:**
- laravel/framework: ^11.0
- laravel/breeze: ^2.x (auth scaffolding)
- laravel/sanctum: ^4.2 (API auth)
- laravel/tinker: ^2.x

---

## Git Repository

**Location:** `c:\proj\scanwarden`
**Remote:** https://github.com/mgelsinger/scanwarden

**Recent Commits:**
- `fc3f19d` - Add Transmuter and Scan History to navigation menu
- `76c13b4` - Implement Phase 5 (Essence Transmuter) and Phase 6 (REST API)
- `1d09bcb` - Phase guide
- `37e47c9` - Init commit

---

## Common Developer Tasks

### Add a New Transmutation Recipe
```php
// In database/seeders/TransmutationRecipeSeeder.php
TransmutationRecipe::create([
    'name' => 'Recipe Name',
    'description' => 'Description',
    'required_inputs' => [
        ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100]
    ],
    'outputs' => [
        ['type' => 'unit_summon', 'sector_id' => 1, 'rarity' => 'epic']
    ],
    'is_active' => true,
    'level_requirement' => 1,
]);
```

### Grant Essence to User (for testing)
```php
php artisan tinker
>>> $user = User::find(1);
>>> $user->essence()->create(['sector_id' => null, 'type' => 'generic', 'amount' => 1000]);
```

### Test API Endpoints
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'

# Get token from response, then:
curl -X GET http://localhost:8000/api/units \
  -H "Authorization: Bearer TOKEN_HERE"
```

---

## Support Context

When the user asks questions, you should be aware of:

1. **All 6 phases are complete** - Don't suggest building features from phases 1-6
2. **The web app is fully functional** - Users can scan UPCs via the web interface
3. **The API is ready** - Mobile apps can be built using the REST API
4. **Tests are passing** - The codebase is stable
5. **Server runs at `http://127.0.0.1:8000`** - Make sure users visit the correct URL
6. **First-time users must select a starter** - This is enforced by middleware

## Current Issue

User reported seeing a different app ("Starter Village" hunting game) when visiting the URL. This suggests:
- Wrong project directory
- Wrong URL
- Cached browser session
- Different Laravel project running

**Resolution:** Ensure user visits `http://127.0.0.1:8000` after running `php artisan serve` in the scanwarden directory.

---

**Last Updated:** December 7, 2025
**Implementation Status:** Production Ready
**Test Coverage:** 31/31 passing
