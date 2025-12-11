<?php

namespace Tests\Feature;

use App\Models\BattleMatch;
use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerProfileTest extends TestCase
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
     * Test profile shows stats and recent battles
     */
    public function test_profile_shows_stats_and_recent_battles(): void
    {
        // Create users
        $player = $this->createUserWithStarter(['name' => 'TestPlayer', 'rating' => 1300]);
        $opponent = $this->createUserWithStarter(['name' => 'Opponent', 'rating' => 1200]);

        // Create teams
        $playerTeam = Team::create(['user_id' => $player->id, 'name' => 'Player Team']);
        $opponentTeam = Team::create(['user_id' => $opponent->id, 'name' => 'Opponent Team']);

        // Create some battles
        BattleMatch::create([
            'user_id' => $player->id,
            'attacker_id' => $player->id,
            'defender_id' => $opponent->id,
            'winner_id' => $player->id,
            'attacker_team_id' => $playerTeam->id,
            'defender_team_id' => $opponentTeam->id,
            'winner' => 'attacker',
            'total_turns' => 5,
            'status' => 'completed',
        ]);

        BattleMatch::create([
            'user_id' => $opponent->id,
            'attacker_id' => $opponent->id,
            'defender_id' => $player->id,
            'winner_id' => $opponent->id,
            'attacker_team_id' => $opponentTeam->id,
            'defender_team_id' => $playerTeam->id,
            'winner' => 'attacker',
            'total_turns' => 6,
            'status' => 'completed',
        ]);

        BattleMatch::create([
            'user_id' => $player->id,
            'attacker_id' => $player->id,
            'defender_id' => $opponent->id,
            'winner_id' => null,
            'attacker_team_id' => $playerTeam->id,
            'defender_team_id' => $opponentTeam->id,
            'winner' => 'draw',
            'total_turns' => 10,
            'status' => 'completed',
        ]);

        // Authenticate as another user to view profile
        $viewer = $this->createUserWithStarter();
        $this->actingAs($viewer);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('TestPlayer');
        $response->assertSee('1300');
        $response->assertSee('Total Battles');
        $response->assertSee('Wins');
        $response->assertSee('Losses');
        $response->assertSee('Draws');
        $response->assertSee('Win Rate');
        $response->assertSee('Opponent');
    }

    /**
     * Test profile works for user with no battles
     */
    public function test_profile_works_for_user_with_no_battles(): void
    {
        $player = $this->createUserWithStarter(['name' => 'Newbie', 'rating' => 0]);

        $this->actingAs($player);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('Newbie');
        $response->assertSee('0');
        $response->assertSee('No battle history yet');
    }

    /**
     * Test authenticated user can view any profile
     */
    public function test_authenticated_user_can_view_any_profile(): void
    {
        $player = $this->createUserWithStarter(['name' => 'TargetPlayer']);
        $viewer = $this->createUserWithStarter();

        $this->actingAs($viewer);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('TargetPlayer');
    }

    /**
     * Test profile requires authentication
     */
    public function test_profile_requires_authentication(): void
    {
        $player = User::factory()->create();

        $response = $this->get(route('players.show', $player));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test profile shows own profile indicator
     */
    public function test_profile_shows_own_profile_indicator(): void
    {
        $player = $this->createUserWithStarter(['name' => 'SelfViewer']);

        $this->actingAs($player);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('(You)');
    }

    /**
     * Test profile displays teams and units
     */
    public function test_profile_displays_teams_and_units(): void
    {
        $player = User::factory()->create();

        // Create a sector
        $sector = Sector::create([
            'name' => 'Tech Sector ' . uniqid(),
            'slug' => 'tech-test-' . uniqid(),
            'description' => 'Test sector',
            'color' => '#004E98',
        ]);

        // Create a team with units
        $team = Team::create([
            'user_id' => $player->id,
            'name' => 'Alpha Team',
        ]);

        $unit = SummonedUnit::create([
            'user_id' => $player->id,
            'sector_id' => $sector->id,
            'name' => 'Test Unit',
            'rarity' => 'Rare',
            'tier' => 'T1',
            'hp' => 100,
            'attack' => 50,
            'defense' => 30,
            'speed' => 40,
        ]);

        $team->units()->attach($unit->id, ['position' => 1]);

        $viewer = $this->createUserWithStarter();
        $this->actingAs($viewer);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('Alpha Team');
        $response->assertSee('Test Unit');
        $response->assertSee('Rare');
    }

    /**
     * Test profile displays sector distribution
     */
    public function test_profile_displays_sector_distribution(): void
    {
        $player = User::factory()->create();

        // Create sectors
        $techSector = Sector::create([
            'name' => 'Tech ' . uniqid(),
            'slug' => 'tech-' . uniqid(),
            'description' => 'Tech sector',
            'color' => '#004E98',
        ]);
        $foodSector = Sector::create([
            'name' => 'Food ' . uniqid(),
            'slug' => 'food-' . uniqid(),
            'description' => 'Food sector',
            'color' => '#FF6B35',
        ]);

        // Create units in different sectors
        SummonedUnit::factory()->count(3)->create([
            'user_id' => $player->id,
            'sector_id' => $techSector->id,
        ]);

        SummonedUnit::factory()->count(2)->create([
            'user_id' => $player->id,
            'sector_id' => $foodSector->id,
        ]);

        $this->actingAs($player);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('Unit Collection');
        $response->assertSee('Units'); // Sectors will have dynamic names
        $response->assertSee('3'); // Tech sector count
        $response->assertSee('2'); // Food sector count
    }

    /**
     * Test profile links to opponent profiles in battle history
     */
    public function test_profile_links_to_opponent_profiles(): void
    {
        $player = $this->createUserWithStarter(['name' => 'Player']);
        $opponent = $this->createUserWithStarter(['name' => 'OpponentName']);

        // Create teams
        $playerTeam = Team::create(['user_id' => $player->id, 'name' => 'Team 1']);
        $opponentTeam = Team::create(['user_id' => $opponent->id, 'name' => 'Team 2']);

        BattleMatch::create([
            'user_id' => $player->id,
            'attacker_id' => $player->id,
            'defender_id' => $opponent->id,
            'winner_id' => $player->id,
            'attacker_team_id' => $playerTeam->id,
            'defender_team_id' => $opponentTeam->id,
            'winner' => 'attacker',
            'total_turns' => 5,
            'status' => 'completed',
        ]);

        $this->actingAs($player);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('OpponentName');
        $response->assertSee(route('players.show', $opponent));
    }

    /**
     * Test profile calculates win rate correctly
     */
    public function test_profile_calculates_win_rate_correctly(): void
    {
        $player = $this->createUserWithStarter();
        $opponent = $this->createUserWithStarter();

        // Create teams
        $playerTeam = Team::create(['user_id' => $player->id, 'name' => 'Player Team']);
        $opponentTeam = Team::create(['user_id' => $opponent->id, 'name' => 'Opponent Team']);

        // Create 3 wins and 1 loss (75% win rate)
        for ($i = 0; $i < 3; $i++) {
            BattleMatch::create([
                'user_id' => $player->id,
                'attacker_id' => $player->id,
                'defender_id' => $opponent->id,
                'winner_id' => $player->id,
                'attacker_team_id' => $playerTeam->id,
                'defender_team_id' => $opponentTeam->id,
                'winner' => 'attacker',
                'total_turns' => 5,
                'status' => 'completed',
            ]);
        }

        BattleMatch::create([
            'user_id' => $player->id,
            'attacker_id' => $player->id,
            'defender_id' => $opponent->id,
            'winner_id' => $opponent->id,
            'attacker_team_id' => $playerTeam->id,
            'defender_team_id' => $opponentTeam->id,
            'winner' => 'defender',
            'total_turns' => 5,
            'status' => 'completed',
        ]);

        $this->actingAs($player);

        $response = $this->get(route('players.show', $player));

        $response->assertStatus(200);
        $response->assertSee('75%'); // Win rate
        $response->assertSee('3'); // Wins
        $response->assertSee('1'); // Losses
        $response->assertSee('4'); // Total battles
    }
}
