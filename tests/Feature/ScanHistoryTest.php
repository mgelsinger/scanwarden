<?php

namespace Tests\Feature;

use App\Models\ScanRecord;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_user_can_view_scan_history(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        // Create some scan records for the user
        ScanRecord::factory()->count(3)->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)->get(route('scan-history.index'));

        $response->assertOk();
        $response->assertSee('Scan History');
        $response->assertSee($sector->name);
    }

    public function test_user_can_filter_scans_by_sector(): void
    {
        $user = User::factory()->create();
        $sector1 = Sector::first();
        $sector2 = Sector::skip(1)->first();

        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        // Create scans from different sectors
        ScanRecord::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector1->id,
            'raw_upc' => '111111111111',
        ]);

        ScanRecord::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector2->id,
            'raw_upc' => '222222222222',
        ]);

        // Filter by sector 1
        $response = $this->actingAs($user)->get(route('scan-history.index', ['sector' => $sector1->id]));

        $response->assertOk();
        $response->assertSee('111111111111');
        $response->assertDontSee('222222222222');
    }

    public function test_scan_history_is_paginated(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        // Create more than 20 scan records (pagination threshold)
        ScanRecord::factory()->count(25)->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)->get(route('scan-history.index'));

        $response->assertOk();
        // Check for pagination links
        $response->assertSee('Next');
    }

    public function test_user_only_sees_their_own_scan_history(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $sector = Sector::first();

        // Create summoned units so users pass middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user1->id]);
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user2->id]);

        // Create scans for both users
        $scan1 = ScanRecord::factory()->create([
            'user_id' => $user1->id,
            'sector_id' => $sector->id,
            'raw_upc' => '111111111111',
        ]);

        $scan2 = ScanRecord::factory()->create([
            'user_id' => $user2->id,
            'sector_id' => $sector->id,
            'raw_upc' => '222222222222',
        ]);

        // User 1 should only see their scan
        $response = $this->actingAs($user1)->get(route('scan-history.index'));

        $response->assertOk();
        $response->assertSee('111111111111');
        $response->assertDontSee('222222222222');
    }

    public function test_empty_scan_history_shows_message(): void
    {
        $user = User::factory()->create();

        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('scan-history.index'));

        $response->assertOk();
        $response->assertSee('No Scans Yet');
        $response->assertSee('Start scanning UPCs');
    }

    public function test_scan_history_displays_rewards(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        // Create a summoned unit so user passes middleware
        \App\Models\SummonedUnit::factory()->create(['user_id' => $user->id]);

        ScanRecord::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'rewards' => [
                'unit_summoned' => true,
                'energy' => 50,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('scan-history.index'));

        $response->assertOk();
        $response->assertSee('Unit Summoned');
        $response->assertSee('50');
        $response->assertSee('Energy');
    }
}
