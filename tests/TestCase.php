<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // If test uses RefreshDatabase, seed essential game data
        if (in_array(RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seedEssentialData();
        }
    }

    protected function seedEssentialData(): void
    {
        $this->seed(\Database\Seeders\SectorSeeder::class);
        $this->seed(\Database\Seeders\EvolutionRuleSeeder::class);
    }
}
