<?php

namespace Tests\Unit\Services;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserEssence;
use App\Models\UserQuest;
use App\Services\QuestProgressService;
use App\Services\QuestRewardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestProgressService $questProgressService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questProgressService = app(QuestProgressService::class);
    }

    /**
     * Test assigning daily quests only once per day
     */
    public function test_assign_daily_quests_once_per_day(): void
    {
        $user = User::factory()->create();

        // Create some daily quests
        Quest::create([
            'slug' => 'daily_test_1',
            'name' => 'Test Daily 1',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 3,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
        ]);

        Quest::create([
            'slug' => 'daily_test_2',
            'name' => 'Test Daily 2',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'battle_pvp',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
        ]);

        // First assignment
        $this->questProgressService->assignDailyQuestsForUser($user);
        $firstCount = UserQuest::where('user_id', $user->id)->count();
        $this->assertGreaterThan(0, $firstCount);

        // Second assignment (should not create duplicates)
        $this->questProgressService->assignDailyQuestsForUser($user);
        $secondCount = UserQuest::where('user_id', $user->id)->count();
        $this->assertEquals($firstCount, $secondCount);
    }

    /**
     * Test incrementing quest progress
     */
    public function test_increment_progress(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_increment',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'achievement',
            'category' => 'scan',
            'target_value' => 10,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
        ]);

        $userQuest = UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 0,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
        ]);

        // Increment progress
        $this->questProgressService->incrementProgress($user, 'scan', 1);
        $userQuest->refresh();

        $this->assertEquals(1, $userQuest->progress);
        $this->assertNotNull($userQuest->last_progress_at);

        // Increment again
        $this->questProgressService->incrementProgress($user, 'scan', 2);
        $userQuest->refresh();

        $this->assertEquals(3, $userQuest->progress);
    }

    /**
     * Test quest auto-completion when target reached
     */
    public function test_quest_auto_completes_when_target_reached(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_complete',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'achievement',
            'category' => 'scan',
            'target_value' => 5,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
        ]);

        $userQuest = UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 4,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
        ]);

        // Increment to complete
        $this->questProgressService->incrementProgress($user, 'scan', 1);
        $userQuest->refresh();

        $this->assertEquals(5, $userQuest->progress);
        $this->assertTrue($userQuest->is_completed);
        $this->assertNotNull($userQuest->completed_at);

        // Verify reward was granted
        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();
        $this->assertNotNull($essence);
        $this->assertEquals(100, $essence->amount);
    }

    /**
     * Test progress is clamped to target value
     */
    public function test_progress_clamped_to_target(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_clamp',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'achievement',
            'category' => 'scan',
            'target_value' => 10,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 100],
            ],
        ]);

        $userQuest = UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 8,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
        ]);

        // Increment by more than needed
        $this->questProgressService->incrementProgress($user, 'scan', 5);
        $userQuest->refresh();

        // Should be clamped to target
        $this->assertEquals(10, $userQuest->progress);
        $this->assertTrue($userQuest->is_completed);
    }

    /**
     * Test expired quests don't get incremented
     */
    public function test_expired_quests_not_incremented(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_expired',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 5,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
        ]);

        $userQuest = UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 2,
            'target_value' => $quest->target_value,
            'assigned_at' => now()->subDay(),
            'expires_at' => now()->subHour(), // Expired
        ]);

        // Try to increment
        $this->questProgressService->incrementProgress($user, 'scan', 1);
        $userQuest->refresh();

        // Should not have been incremented
        $this->assertEquals(2, $userQuest->progress);
    }

    /**
     * Test recently completed quests retrieval
     */
    public function test_recently_completed_quests(): void
    {
        $user = User::factory()->create();

        $quest1 = Quest::create([
            'slug' => 'test_recent_1',
            'name' => 'Test Quest 1',
            'description' => 'Test',
            'type' => 'achievement',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [],
        ]);

        $quest2 = Quest::create([
            'slug' => 'test_recent_2',
            'name' => 'Test Quest 2',
            'description' => 'Test',
            'type' => 'achievement',
            'category' => 'battle',
            'target_value' => 1,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [],
        ]);

        // Complete quest 1 recently
        UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest1->id,
            'progress' => 1,
            'target_value' => 1,
            'is_completed' => true,
            'completed_at' => now()->subMinutes(2),
            'assigned_at' => now()->subHour(),
        ]);

        // Complete quest 2 a long time ago
        UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest2->id,
            'progress' => 1,
            'target_value' => 1,
            'is_completed' => true,
            'completed_at' => now()->subDays(2),
            'assigned_at' => now()->subDays(3),
        ]);

        // Get recently completed (last 5 minutes)
        $recentQuests = $this->questProgressService->recentlyCompletedForUser($user, now()->subMinutes(5));

        $this->assertCount(1, $recentQuests);
        $this->assertEquals($quest1->id, $recentQuests->first()->quest_id);
    }
}
