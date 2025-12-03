<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }

    public function test_scan_page_requires_authentication(): void
    {
        $response = $this->get('/scan');
        $response->assertRedirect('/login');
    }

    public function test_units_page_requires_authentication(): void
    {
        $response = $this->get('/units');
        $response->assertRedirect('/login');
    }

    public function test_teams_page_requires_authentication(): void
    {
        $response = $this->get('/teams');
        $response->assertRedirect('/login');
    }

    public function test_battles_page_requires_authentication(): void
    {
        $response = $this->get('/battles');
        $response->assertRedirect('/login');
    }

    public function test_leaderboard_page_requires_authentication(): void
    {
        $response = $this->get('/leaderboard');
        $response->assertRedirect('/login');
    }

    public function test_lore_page_requires_authentication(): void
    {
        $response = $this->get('/lore');
        $response->assertRedirect('/login');
    }
}
