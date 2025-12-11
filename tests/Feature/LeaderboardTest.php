<?php

namespace Tests\Feature;

use App\Models\BattleMatch;
use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with a starter unit to bypass starter.selected middleware
     */
    private function createUserWithStarter(array $attributes = []): User
    {
        static $counter = 0;
        $counter++;

        $user = User::factory()->create($attributes);
        $sector = Sector::create([
            'name' => 'Test Sector ' . $counter . '-' . uniqid(),
            'slug' => 'test-' . $counter . '-' . uniqid(),
            'description' => 'Test sector',
            'color' => '#000000',
        ]);
        SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);
        return $user;
    }

    /**
     * Test leaderboard orders users by rating
     */
    public function test_leaderboard_orders_users_by_rating(): void
    {
        // Create users with different ratings
        $userHigh = $this->createUserWithStarter(['name' => 'HighRater', 'rating' => 1500]);
        $userMid = $this->createUserWithStarter(['name' => 'MidRater', 'rating' => 1200]);
        $userLow = $this->createUserWithStarter(['name' => 'LowRater', 'rating' => 900]);

        // Authenticate as any user
        $this->actingAs($userMid);

        // Visit leaderboard
        $response = $this->get(route('leaderboard.index'));

        $response->assertStatus(200);

        // Assert users appear in order of rating (high to low)
        $response->assertSeeInOrder([
            'HighRater',
            'MidRater',
            'LowRater',
        ]);
    }

    /**
     * Test leaderboard handles users with zero or null rating
     */
    public function test_leaderboard_handles_zero_rating(): void
    {
        // Create user without battles (default rating)
        $userNoBattles = $this->createUserWithStarter(['name' => 'Newbie', 'rating' => 0]);
        $userWithRating = $this->createUserWithStarter(['name' => 'Veteran', 'rating' => 1300]);

        $this->actingAs($userNoBattles);

        $response = $this->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Newbie');
        $response->assertSee('Veteran');
    }

    /**
     * Test leaderboard requires authentication
     */
    public function test_leaderboard_requires_authentication(): void
    {
        $response = $this->get(route('leaderboard.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test leaderboard displays battle statistics
     */
    public function test_leaderboard_displays_battle_statistics(): void
    {
        // Create users
        $user1 = $this->createUserWithStarter(['name' => 'Fighter', 'rating' => 1400]);
        $user2 = $this->createUserWithStarter(['name' => 'Opponent', 'rating' => 1300]);

        // Create teams
        $team1 = Team::create(['user_id' => $user1->id, 'name' => 'Team 1']);
        $team2 = Team::create(['user_id' => $user2->id, 'name' => 'Team 2']);

        // Create some battles for user1
        BattleMatch::create([
            'user_id' => $user1->id,
            'attacker_id' => $user1->id,
            'defender_id' => $user2->id,
            'winner_id' => $user1->id,
            'attacker_team_id' => $team1->id,
            'defender_team_id' => $team2->id,
            'winner' => 'attacker',
            'total_turns' => 5,
            'status' => 'completed',
        ]);

        BattleMatch::create([
            'user_id' => $user2->id,
            'attacker_id' => $user2->id,
            'defender_id' => $user1->id,
            'winner_id' => $user2->id,
            'attacker_team_id' => $team2->id,
            'defender_team_id' => $team1->id,
            'winner' => 'attacker',
            'total_turns' => 6,
            'status' => 'completed',
        ]);

        $this->actingAs($user1);

        $response = $this->get(route('leaderboard.index'));

        $response->assertStatus(200);
        // Should show total battles and win rate
        $response->assertSee('Fighter');
    }

    /**
     * Test leaderboard shows current user rank
     */
    public function test_leaderboard_shows_current_user_rank(): void
    {
        // Create users with different ratings
        $user1 = $this->createUserWithStarter(['rating' => 1500]);
        $user2 = $this->createUserWithStarter(['rating' => 1200]);
        $user3 = $this->createUserWithStarter(['rating' => 900]);

        // Login as middle-rated user
        $this->actingAs($user2);

        $response = $this->get(route('leaderboard.index'));

        $response->assertStatus(200);
        // Should show "Your Rank" section
        $response->assertSee('Your Rank');
    }

    /**
     * Test leaderboard links to player profiles
     */
    public function test_leaderboard_links_to_player_profiles(): void
    {
        $user = $this->createUserWithStarter(['name' => 'TestPlayer', 'rating' => 1200]);

        $this->actingAs($user);

        $response = $this->get(route('leaderboard.index'));

        $response->assertStatus(200);
        // Check that profile link exists
        $response->assertSee(route('players.show', $user));
    }
}
