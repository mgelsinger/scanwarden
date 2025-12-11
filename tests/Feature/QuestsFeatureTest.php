<?php

namespace Tests\Feature;

use App\Models\Quest;
use App\Models\Sector;
use App\Models\SummonedUnit;
use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestsFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a user with a starter unit (to bypass middleware)
     */
    private function createUserWithStarter(array $attributes = []): User
    {
        static $counter = 0;
        $counter++;

        $user = User::factory()->create($attributes);

        $sector = Sector::firstOrCreate(
            ['slug' => 'test-sector-' . $counter],
            [
                'name' => 'Test Sector ' . $counter,
                'description' => 'Test sector for unit tests',
                'color' => '#000000',
            ]
        );

        SummonedUnit::factory()->create([
            'user_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        return $user;
    }

    /**
     * Test authenticated user can access quests page
     */
    public function test_authenticated_user_can_access_quests_page(): void
    {
        $user = $this->createUserWithStarter();

        $response = $this->actingAs($user)->get(route('quests.index'));

        $response->assertStatus(200);
        $response->assertViewIs('quests.index');
        $response->assertViewHas(['dailyQuests', 'achievements']);
    }

    /**
     * Test guest cannot access quests page
     */
    public function test_guest_cannot_access_quests_page(): void
    {
        $response = $this->get(route('quests.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test daily quests are assigned on dashboard visit
     */
    public function test_daily_quests_assigned_on_dashboard_visit(): void
    {
        $user = $this->createUserWithStarter();

        // Create a daily quest
        Quest::create([
            'slug' => 'daily_test',
            'name' => 'Test Daily Quest',
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

        // Visit dashboard
        $this->actingAs($user)->get(route('dashboard'));

        // Check if quest was assigned
        $this->assertDatabaseHas('user_quests', [
            'user_id' => $user->id,
            'is_completed' => false,
        ]);
    }

    /**
     * Test quest progress can be incremented
     */
    public function test_quest_progress_can_be_incremented(): void
    {
        $user = $this->createUserWithStarter();

        // Create a quest
        $quest = Quest::create([
            'slug' => 'test_progress_quest',
            'name' => 'Test Progress Quest',
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

        // Assign quest to user
        $userQuest = UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 0,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
        ]);

        // Use the quest progress service to increment progress
        $questProgressService = app(\App\Services\QuestProgressService::class);
        $questProgressService->incrementProgress($user, 'scan', 1);

        // Check quest progress
        $userQuest->refresh();
        $this->assertEquals(1, $userQuest->progress);
    }

    /**
     * Test quests page displays daily quests
     */
    public function test_quests_page_displays_daily_quests(): void
    {
        $user = $this->createUserWithStarter();

        $quest = Quest::create([
            'slug' => 'daily_display_test',
            'name' => 'Daily Display Quest',
            'description' => 'Test description for display',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 3,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 30],
            ],
        ]);

        UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 1,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
            'expires_at' => now()->endOfDay(),
        ]);

        $response = $this->actingAs($user)->get(route('quests.index'));

        $response->assertStatus(200);
        $response->assertSee('Daily Display Quest');
        $response->assertSee('Test description for display');
        $response->assertSee('1 / 3');
    }

    /**
     * Test quests page displays achievements
     */
    public function test_quests_page_displays_achievements(): void
    {
        $user = $this->createUserWithStarter();

        $quest = Quest::create([
            'slug' => 'achievement_display_test',
            'name' => 'Achievement Display Quest',
            'description' => 'Test achievement description',
            'type' => 'achievement',
            'category' => 'scan',
            'target_value' => 100,
            'is_daily' => false,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 200],
            ],
        ]);

        UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 50,
            'target_value' => $quest->target_value,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('quests.index'));

        $response->assertStatus(200);
        $response->assertSee('Achievement Display Quest');
        $response->assertSee('Test achievement description');
        $response->assertSee('50 / 100');
    }

    /**
     * Test completed quest shows as completed
     */
    public function test_completed_quest_displays_correctly(): void
    {
        $user = $this->createUserWithStarter();

        $quest = Quest::create([
            'slug' => 'completed_test',
            'name' => 'Completed Quest',
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

        UserQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'progress' => 10,
            'target_value' => $quest->target_value,
            'is_completed' => true,
            'completed_at' => now(),
            'assigned_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('quests.index'));

        $response->assertStatus(200);
        $response->assertSee('Completed Quest');
        $response->assertSee('Completed');
    }
}
