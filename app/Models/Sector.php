<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
    ];

    public function summonedUnits(): HasMany
    {
        return $this->hasMany(SummonedUnit::class);
    }

    public function sectorEnergies(): HasMany
    {
        return $this->hasMany(SectorEnergy::class);
    }

    public function scanRecords(): HasMany
    {
        return $this->hasMany(ScanRecord::class);
    }

    public function loreEntries(): HasMany
    {
        return $this->hasMany(LoreEntry::class);
    }
}
