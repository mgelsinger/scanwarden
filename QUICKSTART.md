# ScanWarden Quick Start Guide

## You're Ready to Play! 🎮

ScanWarden v0.1 is **fully functional** with phases 1-4 complete. The core scanning → summoning gameplay loop is working!

## Start Playing NOW

### 1. Start the Server
```bash
cd c:\proj\scanwarden
/c/xampp/php/php.exe artisan serve
```

Server will start at: `http://localhost:8000`

### 2. Login or Register
- **Test Account:** test@example.com / password
- **Or Register:** Create your own account

### 3. Start Scanning!
1. Click **"Scan"** in the navigation
2. Enter a UPC code (try: `012345678905`)
3. See your rewards:
   - Sector classification
   - Energy gained
   - Unit summoned (if lucky!)

### Example UPCs to Try

| UPC | Expected Sector |
|-----|----------------|
| `012345678905` | Food/Random |
| `042100005264` | Food Sector |
| `790572453903` | Tech Sector |
| `300450147202` | Bio Sector |
| `685387123456` | Household |
| `123456789012` | Random |

**Pro tip:** Your first scan is guaranteed to summon a unit!

## What's Working Right Now ✅

### Scanning System
- ✅ UPC validation and classification
- ✅ 6 unique Sectors with themes
- ✅ Deterministic + biased classification
- ✅ Energy tracking per sector

### Unit Summoning
- ✅ 5 rarity tiers (Common → Legendary)
- ✅ Dynamic stat generation
- ✅ Passive abilities for rare+ units
- ✅ Sector-specific names
- ✅ Beautiful result displays

### Database
- ✅ All models and relationships
- ✅ Migrations run successfully
- ✅ Sectors and Evolution Rules seeded

## What's Coming Next

See the main README.md for detailed implementation notes on:
- **Phase 5:** Unit Evolution (use sector energy to power up)
- **Phase 6:** Teams & Battles (build teams, fight others)
- **Phase 7:** Ratings & Leaderboard (climb the ranks)
- **Phase 8:** Lore System (unlock world secrets)
- **Phase 9:** Enhanced Dashboard & UX
- **Phase 10:** Authorization & Tests
- **Phase 11:** Final polish & deployment

## Troubleshooting

### Server Won't Start
```bash
# Make sure PHP is accessible
/c/xampp/php/php.exe --version

# Check for port conflicts
# Laravel uses port 8000 by default
```

### Database Errors
```bash
# Reset database
/c/xampp/php/php.exe artisan migrate:fresh --seed
```

### View Errors
```bash
# Clear caches
/c/xampp/php/php.exe artisan view:clear
/c/xampp/php/php.exe artisan config:clear
```

## Useful Commands

### Database
```bash
# See current state
/c/xampp/php/php.exe artisan migrate:status

# Fresh start with data
/c/xampp/php/php.exe artisan migrate:fresh --seed

# Interactive database console
/c/xampp/php/php.exe artisan tinker
```

### Development
```bash
# List all routes
/c/xampp/php/php.exe artisan route:list

# Clear all caches
/c/xampp/php/php.exe artisan optimize:clear

# Run tests (when implemented)
/c/xampp/php/php.exe artisan test
```

### In Tinker (Interactive Console)
```php
// Check sectors
Sector::all();

// Check your units
User::find(1)->summonedUnits;

// See sector energies
User::find(1)->sectorEnergies()->with('sector')->get();

// Total scans
ScanRecord::count();
```

## File Locations

### Key Files to Explore
- **Models:** `app/Models/`
- **Controllers:** `app/Http/Controllers/`
- **Services:** `app/Services/`
- **Views:** `resources/views/`
- **Routes:** `routes/web.php`
- **Migrations:** `database/migrations/`
- **Seeders:** `database/seeders/`

### Configuration
- **Environment:** `.env`
- **Database:** `database/database.sqlite`
- **App Config:** `config/app.php`

## Database Schema

### Key Tables
```
users
├── id, name, email, password
├── rating (default: 1000)
└── timestamps

sectors
├── id, name, description, color
└── timestamps

summoned_units
├── id, user_id, sector_id
├── name, rarity, tier, evolution_level
├── hp, attack, defense, speed
├── passive_ability
└── timestamps

scan_records
├── id, user_id, sector_id
├── raw_upc
├── rewards (JSON)
└── timestamps

sector_energies
├── id, user_id, sector_id
├── current_energy
└── timestamps
```

## Next Steps for Development

### To Implement Phase 5 (Evolution):
1. Create `app/Services/EvolutionService.php`
2. Create `app/Http/Controllers/UnitsController.php`
3. Add routes for `/units`
4. Create views in `resources/views/units/`

### To Implement Phase 6 (Battles):
1. Create `app/Services/BattleSimulatorService.php`
2. Create `app/Http/Controllers/TeamsController.php`
3. Create `app/Http/Controllers/BattleMatchesController.php`
4. Create `app/Jobs/ResolveMatchJob.php`
5. Add routes and views

See README.md for complete implementation details!

## Having Fun?

Try these challenges:
- ✨ Summon a Legendary unit (1% chance!)
- 🎯 Collect units from all 6 Sectors
- 💪 Accumulate 100+ energy in one Sector
- 📊 Scan 50+ different UPCs
- 🔮 Find the perfect balance of stats

## Support

- 📖 Full documentation: `README.md`
- 🔧 Implementation guides for Phases 5-11 in README
- 🐛 Check Laravel logs: `storage/logs/laravel.log`
- 💬 Laravel docs: https://laravel.com/docs

---

**Ready to build your Warden army? Start scanning! 🔍**
