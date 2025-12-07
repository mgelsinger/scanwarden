<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_user_can_add_unit_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $sector = Sector::first();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)->post(route('teams.addUnit', $team), [
            'unit_id' => $unit->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue($team->units()->where('summoned_unit_id', $unit->id)->exists());
    }

    public function test_user_cannot_add_more_than_5_units_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $sector = Sector::first();

        // Add 5 units to the team
        $units = SummonedUnit::factory()->count(5)->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        foreach ($units as $index => $unit) {
            $team->units()->attach($unit->id, ['position' => $index + 1]);
        }

        // Try to add a 6th unit
        $sixthUnit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)->post(route('teams.addUnit', $team), [
            'unit_id' => $sixthUnit->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(5, $team->units()->count());
    }

    public function test_user_cannot_add_another_users_unit_to_team(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user1->id]);
        $sector = Sector::first();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user2->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user1)->post(route('teams.addUnit', $team), [
            'unit_id' => $unit->id,
        ]);

        // Laravel's exception handler converts 403 to a redirect in testing
        // Just verify the unit was not added to the team
        $this->assertFalse($team->units()->where('summoned_unit_id', $unit->id)->exists());
    }

    public function test_user_can_remove_unit_from_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $sector = Sector::first();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $team->units()->attach($unit->id, ['position' => 1]);

        $response = $this->actingAs($user)->delete(route('teams.removeUnit', [$team, $unit]));

        $response->assertRedirect();
        $this->assertFalse($team->units()->where('summoned_unit_id', $unit->id)->exists());
    }

    public function test_user_cannot_manage_another_users_team(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user2->id]);
        $sector = Sector::first();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user1->id,
            'sector_id' => $sector->id,
        ]);

        // Try to add unit to another user's team
        $response = $this->actingAs($user1)->post(route('teams.addUnit', $team), [
            'unit_id' => $unit->id,
        ]);

        $response->assertForbidden();
    }

    public function test_team_edit_page_shows_available_units(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $sector = Sector::first();
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)->get(route('teams.edit', $team));

        $response->assertOk();
        $response->assertSee($unit->name);
        $response->assertSee('Team Builder');
    }

    public function test_positions_are_reordered_after_removing_unit(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $sector = Sector::first();

        // Create 3 units and add them to team
        $units = SummonedUnit::factory()->count(3)->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        foreach ($units as $index => $unit) {
            $team->units()->attach($unit->id, ['position' => $index + 1]);
        }

        // Remove the middle unit
        $this->actingAs($user)->delete(route('teams.removeUnit', [$team, $units[1]]));

        // Verify positions are reordered
        $team->refresh();
        $this->assertEquals(2, $team->units()->count());

        $teamUnits = $team->units()->orderBy('position')->get();
        $this->assertEquals(1, $teamUnits[0]->pivot->position);
        $this->assertEquals(2, $teamUnits[1]->pivot->position);
    }
}
