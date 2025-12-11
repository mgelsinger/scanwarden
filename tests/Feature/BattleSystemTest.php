<?php

namespace Tests\Feature;

use App\Models\BattleMatch;
use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_user_can_initiate_practice_battle(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        // Create a team with units
        $team = Team::factory()->create(['user_id' => $user->id, 'name' => 'My Team']);
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);
        $team->units()->attach($unit->id, ['position' => 1]);

        // Get initial rating (fresh from DB)
        $user->refresh();
        $initialRating = $user->rating;

        $response = $this->actingAs($user)->post(route('battles.practice'), [
            'team_id' => $team->id,
            'difficulty' => 'medium',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Assert battle match was created
        $this->assertDatabaseHas('battle_matches', [
            'user_id' => $user->id,
            'attacker_team_id' => $team->id,
            'status' => 'completed',
            'rating_change' => 0, // Practice doesn't change rating
        ]);

        // Assert rating didn't change
        $user->refresh();
        $this->assertEquals($initialRating, $user->rating);
    }

    public function test_user_can_initiate_pvp_battle_and_rating_updates(): void
    {
        $user1 = User::factory()->create(['rating' => 1200]);
        $user2 = User::factory()->create(['rating' => 1200]);
        $sector = Sector::first();

        // Create teams for both users
        $team1 = Team::factory()->create(['user_id' => $user1->id]);
        $team2 = Team::factory()->create(['user_id' => $user2->id]);

        // Add strong unit to user1's team
        $unit1 = SummonedUnit::factory()->create([
            'user_id' => $user1->id,
            'sector_id' => $sector->id,
            'hp' => 200,
            'attack' => 50,
            'defense' => 30,
            'speed' => 40,
        ]);
        $team1->units()->attach($unit1->id, ['position' => 1]);

        // Add weaker unit to user2's team
        $unit2 = SummonedUnit::factory()->create([
            'user_id' => $user2->id,
            'sector_id' => $sector->id,
            'hp' => 50,
            'attack' => 10,
            'defense' => 5,
            'speed' => 10,
        ]);
        $team2->units()->attach($unit2->id, ['position' => 1]);

        $this->actingAs($user1)->post(route('battles.pvp'), [
            'team_id' => $team1->id,
        ]);

        // Assert battle match was created
        $match = BattleMatch::where('attacker_id', $user1->id)->latest()->first();
        $this->assertNotNull($match);
        $this->assertEquals('completed', $match->status);

        // Check ratings were updated
        $user1->refresh();
        $user2->refresh();

        if ($match->winner === 'attacker') {
            // User1 won: +10, User2 lost: -5
            $this->assertEquals(1210, $user1->rating);
            $this->assertEquals(1195, $user2->rating);
        } else {
            // User1 lost: -5, User2 won: +10
            $this->assertEquals(1195, $user1->rating);
            $this->assertEquals(1210, $user2->rating);
        }
    }

    public function test_battles_index_shows_rating_and_teams(): void
    {
        $user = User::factory()->create(['rating' => 1337]);
        $sector = Sector::first();

        $team = Team::factory()->create(['user_id' => $user->id, 'name' => 'Alpha Squad']);
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);
        $team->units()->attach($unit->id, ['position' => 1]);

        $response = $this->actingAs($user)->get(route('battles.index'));

        $response->assertOk();
        $response->assertSee('1337'); // Rating displayed
        $response->assertSee('Alpha Squad'); // Team name
        $response->assertSee('Practice Battle'); // Button
        $response->assertSee('PvP Battle'); // Button
    }

    public function test_practice_battle_shows_in_history(): void
    {
        $user = User::factory()->create();
        $sector = Sector::first();

        $team = Team::factory()->create(['user_id' => $user->id]);
        $unit = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);
        $team->units()->attach($unit->id, ['position' => 1]);

        $this->actingAs($user)->post(route('battles.practice'), [
            'team_id' => $team->id,
        ]);

        $response = $this->actingAs($user)->get(route('battles.index'));

        $response->assertOk();
        $response->assertSee('AI Dummy Team');
        $response->assertSee('No rating change');
    }

    public function test_pvp_battle_shows_opponent_name_in_history(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $sector = Sector::first();

        $team1 = Team::factory()->create(['user_id' => $user1->id]);
        $team2 = Team::factory()->create(['user_id' => $user2->id]);

        $unit1 = SummonedUnit::factory()->create([
            'user_id' => $user1->id,
            'sector_id' => $sector->id,
        ]);
        $unit2 = SummonedUnit::factory()->create([
            'user_id' => $user2->id,
            'sector_id' => $sector->id,
        ]);

        $team1->units()->attach($unit1->id, ['position' => 1]);
        $team2->units()->attach($unit2->id, ['position' => 1]);

        $this->actingAs($user1)->post(route('battles.pvp'), [
            'team_id' => $team1->id,
        ]);

        $response = $this->actingAs($user1)->get(route('battles.index'));

        $response->assertOk();
        // Should show opponent's name
        $response->assertSee('Bob');
    }

    public function test_rating_cannot_go_below_zero(): void
    {
        $user = User::factory()->create(['rating' => 3]);
        $opponent = User::factory()->create(['rating' => 1200]);
        $sector = Sector::first();

        $team1 = Team::factory()->create(['user_id' => $user->id]);
        $team2 = Team::factory()->create(['user_id' => $opponent->id]);

        // Give opponent a much stronger team
        $unit1 = SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
            'hp' => 10,
            'attack' => 5,
            'defense' => 2,
            'speed' => 5,
        ]);
        $unit2 = SummonedUnit::factory()->create([
            'user_id' => $opponent->id,
            'sector_id' => $sector->id,
            'hp' => 200,
            'attack' => 50,
            'defense' => 30,
            'speed' => 40,
        ]);

        $team1->units()->attach($unit1->id, ['position' => 1]);
        $team2->units()->attach($unit2->id, ['position' => 1]);

        $this->actingAs($user)->post(route('battles.pvp'), [
            'team_id' => $team1->id,
        ]);

        $user->refresh();

        // Rating should not go below 0
        $this->assertGreaterThanOrEqual(0, $user->rating);
    }

    public function test_cannot_battle_with_empty_team(): void
    {
        $user = User::factory()->create();

        $team = Team::factory()->create(['user_id' => $user->id]);
        // No units added

        $initialBattleCount = BattleMatch::count();

        $response = $this->actingAs($user)->post(route('battles.practice'), [
            'team_id' => $team->id,
        ]);

        $response->assertRedirect();

        // Verify no battle was created
        $this->assertEquals($initialBattleCount, BattleMatch::count());
    }

    public function test_draw_results_in_no_rating_change(): void
    {
        $user1 = User::factory()->create(['rating' => 1200]);
        $user2 = User::factory()->create(['rating' => 1200]);
        $sector = Sector::first();

        $team1 = Team::factory()->create(['user_id' => $user1->id]);
        $team2 = Team::factory()->create(['user_id' => $user2->id]);

        // Create identical tank units that should timeout
        for ($i = 0; $i < 2; $i++) {
            $unit = SummonedUnit::factory()->create([
                'user_id' => $user1->id,
                'sector_id' => $sector->id,
                'hp' => 500,
                'attack' => 1,
                'defense' => 50,
                'speed' => 20,
            ]);
            $team1->units()->attach($unit->id, ['position' => $i + 1]);

            $unit = SummonedUnit::factory()->create([
                'user_id' => $user2->id,
                'sector_id' => $sector->id,
                'hp' => 500,
                'attack' => 1,
                'defense' => 50,
                'speed' => 20,
            ]);
            $team2->units()->attach($unit->id, ['position' => $i + 1]);
        }

        $this->actingAs($user1)->post(route('battles.pvp'), [
            'team_id' => $team1->id,
        ]);

        $match = BattleMatch::where('attacker_id', $user1->id)->latest()->first();

        // If draw, no rating changes
        if ($match->winner === 'draw') {
            $this->assertEquals(0, $match->rating_change);

            $user1->refresh();
            $user2->refresh();

            $this->assertEquals(1200, $user1->rating);
            $this->assertEquals(1200, $user2->rating);
        } else {
            // If not a draw (due to HP differences), rating changes should occur
            $this->assertNotEquals(0, $match->rating_change);
        }
    }
}
