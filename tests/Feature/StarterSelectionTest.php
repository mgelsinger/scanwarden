<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_new_user_is_redirected_to_starter_selection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('starter.index'));
    }

    public function test_starter_selection_page_displays_starters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('starter.index'));

        $response->assertOk();
        $response->assertSee('Choose Your Starter');
        $response->assertSee('Aegis Guardian');
        $response->assertSee('Spark Striker');
    }

    public function test_user_can_select_a_starter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('summoned_units', [
            'user_id' => $user->id,
            'name' => 'Aegis Guardian',
            'source' => 'starter',
        ]);
    }

    public function test_user_cannot_select_starter_twice(): void
    {
        $user = User::factory()->create();

        // Select first starter
        $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        // Try to select another
        $response = $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'spark_striker',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals(1, $user->summonedUnits()->count());
    }

    public function test_user_with_starter_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('starter.store'), [
            'starter_key' => 'aegis_guardian',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}
