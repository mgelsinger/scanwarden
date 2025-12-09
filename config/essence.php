<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generic Essence Generation
    |--------------------------------------------------------------------------
    |
    | Configuration for generic essence rewards on scans.
    | Generic essence can be used for any sector transmutation.
    |
    */
    'generic' => [
        'chance' => 0.50,      // 50% chance to grant generic essence
        'min'    => 5,
        'max'    => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sector-Specific Essence Generation
    |--------------------------------------------------------------------------
    |
    | Configuration for sector-specific essence rewards on scans.
    | Sector essence matches the scanned UPC's classified sector.
    |
    */
    'sector' => [
        'chance' => 0.35,      // 35% chance to grant sector-specific essence
        'min'    => 3,
        'max'    => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Summon Bonus Essence
    |--------------------------------------------------------------------------
    |
    | Additional essence granted when a unit is summoned during a scan.
    | This bonus celebrates successful summons.
    |
    */
    'summon_bonus' => [
        'enabled' => true,
        'min'     => 5,
        'max'     => 20,
    ],

];
