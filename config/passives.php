<?php

/**
 * Passive Abilities Configuration
 *
 * Registry of all passive abilities/traits available in the game.
 * Each passive has a unique key, name, and description shown to players.
 *
 * The key maps to a corresponding PassiveAbility class in app/Services/Battle/Passives/
 */
return [
    'tech_overclock' => [
        'name' => 'Overclocked Systems',
        'description' => 'First attack deals +20% damage.',
    ],

    'bio_regeneration' => [
        'name' => 'Regenerative Tissue',
        'description' => 'Heals 10% max HP at the end of each of its turns.',
    ],

    'arcane_surge' => [
        'name' => 'Arcane Surge',
        'description' => 'Gains +5 Speed for the first 3 turns.',
    ],

    'legendary_aura' => [
        'name' => 'Mythic Presence',
        'description' => 'Deals +10% damage and takes 10% less damage.',
    ],

    // Future passives can be added here
    // 'industrial_fortress' => [
    //     'name' => 'Fortress Stance',
    //     'description' => 'Takes 20% less damage from physical attacks.',
    // ],
];
