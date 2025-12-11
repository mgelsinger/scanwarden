<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionRule extends Model
{
    protected $fillable = [
        'from_tier',
        'to_tier',
        'required_sector_energy',
        'required_generic_essence',
        'required_sector_essence',
        'hp_multiplier',
        'attack_multiplier',
        'defense_multiplier',
        'speed_multiplier',
        'new_name_suffix',
        'new_trait',
    ];
}
