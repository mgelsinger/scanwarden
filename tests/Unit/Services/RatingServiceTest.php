<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Battle\BattleResult;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RatingService $ratingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ratingService = new RatingService();
    }

    /**
     * Test attacker win rating changes
     */
    public function test_attacker_win_rating_changes(): void
    {
        $attacker = User::factory()->create(['rating' => 1200]);
        $defender = User::factory()->create(['rating' => 1200]);

        $result = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $attacker->id,
            turns: [],
            finalStates: [],
            totalTurns: 5,
            attackerSurvivors: 3,
            defenderSurvivors: 0
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // Attacker should gain +10, defender should lose -5
        $this->assertEquals(1200, $ratings['attacker']['old_rating']);
        $this->assertEquals(1210, $ratings['attacker']['new_rating']);
        $this->assertEquals(10, $ratings['attacker']['change']);

        $this->assertEquals(1200, $ratings['defender']['old_rating']);
        $this->assertEquals(1195, $ratings['defender']['new_rating']);
        $this->assertEquals(-5, $ratings['defender']['change']);

        // Verify DB was updated
        $attacker->refresh();
        $defender->refresh();
        $this->assertEquals(1210, $attacker->rating);
        $this->assertEquals(1195, $defender->rating);
    }

    /**
     * Test defender win rating changes
     */
    public function test_defender_win_rating_changes(): void
    {
        $attacker = User::factory()->create(['rating' => 1300]);
        $defender = User::factory()->create(['rating' => 1250]);

        $result = new BattleResult(
            outcome: 'defender_win',
            winnerUserId: $defender->id,
            turns: [],
            finalStates: [],
            totalTurns: 8,
            attackerSurvivors: 0,
            defenderSurvivors: 2
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // Attacker should lose -5, defender should gain +10
        $this->assertEquals(1300, $ratings['attacker']['old_rating']);
        $this->assertEquals(1295, $ratings['attacker']['new_rating']);
        $this->assertEquals(-5, $ratings['attacker']['change']);

        $this->assertEquals(1250, $ratings['defender']['old_rating']);
        $this->assertEquals(1260, $ratings['defender']['new_rating']);
        $this->assertEquals(10, $ratings['defender']['change']);

        // Verify DB was updated
        $attacker->refresh();
        $defender->refresh();
        $this->assertEquals(1295, $attacker->rating);
        $this->assertEquals(1260, $defender->rating);
    }

    /**
     * Test draw gives no rating change
     */
    public function test_draw_gives_no_rating_change(): void
    {
        $attacker = User::factory()->create(['rating' => 1400]);
        $defender = User::factory()->create(['rating' => 1350]);

        $result = new BattleResult(
            outcome: 'draw',
            winnerUserId: null,
            turns: [],
            finalStates: [],
            totalTurns: 50,
            attackerSurvivors: 1,
            defenderSurvivors: 1
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // No rating changes on draw
        $this->assertEquals(1400, $ratings['attacker']['old_rating']);
        $this->assertEquals(1400, $ratings['attacker']['new_rating']);
        $this->assertEquals(0, $ratings['attacker']['change']);

        $this->assertEquals(1350, $ratings['defender']['old_rating']);
        $this->assertEquals(1350, $ratings['defender']['new_rating']);
        $this->assertEquals(0, $ratings['defender']['change']);

        // Verify DB unchanged
        $attacker->refresh();
        $defender->refresh();
        $this->assertEquals(1400, $attacker->rating);
        $this->assertEquals(1350, $defender->rating);
    }

    /**
     * Test rating floor (cannot go below 0)
     */
    public function test_rating_floor_prevents_negative_ratings(): void
    {
        // Defender at 0 rating
        $attacker = User::factory()->create(['rating' => 1200]);
        $defender = User::factory()->create(['rating' => 0]);

        $result = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $attacker->id,
            turns: [],
            finalStates: [],
            totalTurns: 3,
            attackerSurvivors: 5,
            defenderSurvivors: 0
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // Defender should stay at 0, not go negative
        $this->assertEquals(0, $ratings['defender']['old_rating']);
        $this->assertEquals(0, $ratings['defender']['new_rating']);
        $this->assertEquals(-5, $ratings['defender']['change']); // Change is still -5, but floor applied

        $defender->refresh();
        $this->assertEquals(0, $defender->rating);
    }

    /**
     * Test rating floor with defender at 3 rating
     */
    public function test_rating_floor_with_low_rating(): void
    {
        $attacker = User::factory()->create(['rating' => 1000]);
        $defender = User::factory()->create(['rating' => 3]);

        $result = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $attacker->id,
            turns: [],
            finalStates: [],
            totalTurns: 2,
            attackerSurvivors: 4,
            defenderSurvivors: 0
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // Defender should go to 0 (3 - 5 = -2, but floor at 0)
        $this->assertEquals(3, $ratings['defender']['old_rating']);
        $this->assertEquals(0, $ratings['defender']['new_rating']);

        $defender->refresh();
        $this->assertEquals(0, $defender->rating);
    }

    /**
     * Test rating calculations handle missing rating field properly
     */
    public function test_rating_calculations_with_unset_rating(): void
    {
        // Create users and manually unset rating to test fallback logic
        $attacker = User::factory()->create(['rating' => 1200]);
        $defender = User::factory()->create(['rating' => 1200]);

        // Manually unset rating attribute (simulating legacy data)
        $attacker->rating = null;
        $defender->rating = null;

        $result = new BattleResult(
            outcome: 'attacker_win',
            winnerUserId: $attacker->id,
            turns: [],
            finalStates: [],
            totalTurns: 4,
            attackerSurvivors: 2,
            defenderSurvivors: 0
        );

        $ratings = $this->ratingService->applyBattleResult($attacker, $defender, $result);

        // Should use default rating of 1200 when rating is null
        $this->assertEquals(1200, $ratings['attacker']['old_rating']);
        $this->assertEquals(1210, $ratings['attacker']['new_rating']);

        $this->assertEquals(1200, $ratings['defender']['old_rating']);
        $this->assertEquals(1195, $ratings['defender']['new_rating']);
    }

    /**
     * Test preview rating changes without applying
     */
    public function test_preview_rating_changes(): void
    {
        $attacker = User::factory()->create(['rating' => 1500]);
        $defender = User::factory()->create(['rating' => 1450]);

        $preview = $this->ratingService->previewRatingChanges($attacker, $defender, 'attacker_win');

        // Should show what would happen
        $this->assertEquals(1500, $preview['attacker']['old_rating']);
        $this->assertEquals(1510, $preview['attacker']['new_rating']);
        $this->assertEquals(10, $preview['attacker']['change']);

        $this->assertEquals(1450, $preview['defender']['old_rating']);
        $this->assertEquals(1445, $preview['defender']['new_rating']);
        $this->assertEquals(-5, $preview['defender']['change']);

        // Database should NOT be updated
        $attacker->refresh();
        $defender->refresh();
        $this->assertEquals(1500, $attacker->rating);
        $this->assertEquals(1450, $defender->rating);
    }

    /**
     * Test get default rating
     */
    public function test_get_default_rating(): void
    {
        $defaultRating = $this->ratingService->getDefaultRating();
        $this->assertEquals(1200, $defaultRating);
    }
}
