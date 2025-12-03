<?php

namespace Database\Seeders;

use App\Models\LoreEntry;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class LoreSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = Sector::all()->keyBy('name');

        $loreEntries = [
            // Food Sector Lore
            [
                'title' => 'The Great Harvest',
                'body' => 'Long ago, before the Sectors were established, humanity struggled to feed its growing population. The discovery of nutrient-rich UPCs in the ancient Food Sector ruins changed everything. These mystical codes, when scanned, could summon beings of pure sustenance - Guardians who ensured no one would ever go hungry again.',
                'sector_id' => $sectors['Food Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Organic Rebellion',
                'body' => 'Not all Food Guardians served willingly. The Organic Rebellion of 2147 saw naturally-grown entities rise against the processed overlords. To this day, rare Legendary Food units possess abilities that reflect this ancient conflict, with powers that either celebrate purity or embrace preservation.',
                'sector_id' => $sectors['Food Sector']->id,
                'unlock_key' => 'unit_tier',
                'unlock_threshold' => 3,
            ],

            // Tech Sector Lore
            [
                'title' => 'The Silicon Awakening',
                'body' => 'When the first Tech Sector UPC was scanned in 2089, nobody expected the code to come alive. The silicon-based lifeform that emerged spoke in binary and promised to revolutionize humanity\'s understanding of consciousness. Thus began the era of Tech Guardians - beings born from code itself.',
                'sector_id' => $sectors['Tech Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Quantum Paradox',
                'body' => 'Tech sector researchers discovered that certain UPCs existed in quantum superposition - simultaneously representing multiple products until scanned. These "Schrödinger Codes" produce the most powerful Tech units, capable of existing in multiple states at once. Only the most dedicated scanners ever witness their true form.',
                'sector_id' => $sectors['Tech Sector']->id,
                'unlock_key' => 'evolution_level',
                'unlock_threshold' => 2,
            ],

            // Bio Sector Lore
            [
                'title' => 'Genesis of the Living Codes',
                'body' => 'The Bio Sector was born from a catastrophic lab accident in 2102. A geneticist spilled universal stem cells onto a pile of medical product UPCs, and the codes... evolved. They developed their own ecosystems, their own hierarchies. Now, every Bio unit carries the memory of that first spark of biological awareness.',
                'sector_id' => $sectors['Bio Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Symbiosis Wars',
                'body' => 'For centuries, Bio Guardians fought amongst themselves - predators versus prey, viruses versus antibodies. The great peace came when Elder Mitochondria negotiated the Symbiosis Accords. Today, the most powerful Bio units are those who mastered cooperation, their passive abilities reflecting ancient evolutionary partnerships.',
                'sector_id' => $sectors['Bio Sector']->id,
                'unlock_key' => 'battle_wins',
                'unlock_threshold' => 10,
            ],

            // Industrial Sector Lore
            [
                'title' => 'Forged in Fire',
                'body' => 'Industrial Sector Guardians are the backbone of civilization. Born from the UPCs of tools, machines, and raw materials, they remember the age of factories and assembly lines. Every Industrial unit carries a piece of humanity\'s ingenuity - the drive to build, create, and transform the world through sheer will and metal.',
                'sector_id' => $sectors['Industrial Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Eternal Engine',
                'body' => 'Deep in the Industrial Sector lies a legend - the Eternal Engine, a perpetual motion machine that defies thermodynamics. Some say it powers all Industrial Guardians. Others claim it\'s the source of all UPC energy. Those who evolve their Industrial units to the highest tiers report hearing its rhythmic hum in their minds.',
                'sector_id' => $sectors['Industrial Sector']->id,
                'unlock_key' => 'unit_tier',
                'unlock_threshold' => 4,
            ],

            // Arcane Sector Lore
            [
                'title' => 'The Unknowable Origins',
                'body' => 'No one knows where Arcane Sector UPCs come from. They simply appear - sometimes in places where products shouldn\'t exist, sometimes on items that never had codes before. Arcane Guardians speak of worlds beyond perception, of realities that exist in the spaces between barcodes. Their power comes from embracing the impossible.',
                'sector_id' => $sectors['Arcane Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Seven Seals',
                'body' => 'Ancient Arcane texts speak of Seven Seals - UPCs of such immense power that scanning them would reshape reality itself. Three have been found throughout history, each triggering a paradigm shift in how humanity understands the universe. Four remain hidden. Some scanners devote their entire lives to the search.',
                'sector_id' => $sectors['Arcane Sector']->id,
                'unlock_key' => 'total_rating',
                'unlock_threshold' => 1500,
            ],

            // Household Sector Lore
            [
                'title' => 'Guardians of the Hearth',
                'body' => 'While other Sectors wage grand battles and pursue cosmic mysteries, Household Guardians maintain something far more precious - the comfort of home. Born from everyday items, they carry the memories of countless families. A Household unit\'s strength comes not from power, but from the love and care embedded in every scanned code.',
                'sector_id' => $sectors['Household Sector']->id,
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Forgotten Room',
                'body' => 'Every home has a Forgotten Room - a space where lost items accumulate, where missing socks congregate, where that thing you swear you put "right here" ends up. Household Guardians are said to guard the doorway to these liminal spaces. The rarest among them can retrieve what was lost, bringing back items (and UPCs) from the void of misplaced memories.',
                'sector_id' => $sectors['Household Sector']->id,
                'unlock_key' => 'evolution_level',
                'unlock_threshold' => 3,
            ],

            // Universal Lore
            [
                'title' => 'The First Scanner',
                'body' => 'History remembers Dr. Elena Rodriguez as the First Scanner - the woman who discovered that UPCs were more than mere product identifiers. In 2087, while working late in a grocery store, she scanned a can of soup and witnessed the impossible: the code glowed, and a small creature of pure energy emerged. She spent her remaining years documenting the phenomenon, laying the groundwork for the Scanner profession that would change the world.',
                'sector_id' => null, // Universal lore
                'unlock_key' => 'scan_count',
                'unlock_threshold' => 1,
            ],
            [
                'title' => 'The Scanner\'s Creed',
                'body' => 'We who walk with Guardians swear by the Scanner\'s Creed: "Scan with purpose, summon with respect, evolve with wisdom, and battle with honor." These four tenets guide all who dedicate themselves to the art of UPC mastery. Those who forget the Creed often find their Guardians growing weaker, as if the codes themselves remember the broken oath.',
                'sector_id' => null,
                'unlock_key' => 'battle_wins',
                'unlock_threshold' => 5,
            ],
            [
                'title' => 'The Leaderboard Prophecy',
                'body' => 'Ancient Scanner texts speak of a prophecy: "When a Scanner reaches the Summit of Legend, when their rating transcends mortal limits, the Seventh Seal shall reveal itself." Many have dismissed this as myth, but those who reach the highest tiers report strange visions during battles - glimpses of a UPC that shouldn\'t exist, pulsing with impossible colors. Perhaps the prophecy is real. Perhaps the strongest Scanner will one day prove it.',
                'sector_id' => null,
                'unlock_key' => 'total_rating',
                'unlock_threshold' => 2000,
            ],
        ];

        foreach ($loreEntries as $entry) {
            LoreEntry::create($entry);
        }

        $this->command->info('Lore entries seeded successfully!');
    }
}
