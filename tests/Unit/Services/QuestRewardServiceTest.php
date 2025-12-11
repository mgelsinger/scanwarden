<?php

namespace Tests\Unit\Services;

use App\Models\Quest;
use App\Models\Sector;
use App\Models\SectorEnergy;
use App\Models\User;
use App\Models\UserEssence;
use App\Services\QuestRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestRewardServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestRewardService $questRewardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questRewardService = new QuestRewardService();
    }

    /**
     * Test granting generic essence reward
     */
    public function test_grant_generic_essence_reward(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_generic_essence',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
            ],
        ]);

        $this->questRewardService->grantRewards($user, $quest);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(50, $essence->amount);
    }

    /**
     * Test granting sector essence reward
     */
    public function test_grant_sector_essence_reward(): void
    {
        $user = User::factory()->create();

        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $quest = Quest::create([
            'slug' => 'test_sector_essence',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'sector', 'sector_id' => $sector->id, 'amount' => 30],
            ],
        ]);

        $this->questRewardService->grantRewards($user, $quest);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(30, $essence->amount);
    }

    /**
     * Test granting sector energy reward
     */
    public function test_grant_sector_energy_reward(): void
    {
        $user = User::factory()->create();

        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $quest = Quest::create([
            'slug' => 'test_sector_energy',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 20],
            ],
        ]);

        $this->questRewardService->grantRewards($user, $quest);

        $sectorEnergy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();

        $this->assertNotNull($sectorEnergy);
        $this->assertEquals(20, $sectorEnergy->current_energy);
    }

    /**
     * Test granting multiple rewards at once
     */
    public function test_grant_multiple_rewards(): void
    {
        $user = User::factory()->create();

        $sector = Sector::create([
            'name' => 'Test Sector',
            'slug' => 'test',
            'description' => 'Test',
            'color' => '#000000',
        ]);

        $quest = Quest::create([
            'slug' => 'test_multiple_rewards',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 50],
                ['type' => 'essence', 'essence_type' => 'sector', 'sector_id' => $sector->id, 'amount' => 30],
                ['type' => 'sector_energy', 'sector_id' => $sector->id, 'amount' => 20],
            ],
        ]);

        $this->questRewardService->grantRewards($user, $quest);

        // Check generic essence
        $genericEssence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();
        $this->assertNotNull($genericEssence);
        $this->assertEquals(50, $genericEssence->amount);

        // Check sector essence
        $sectorEssence = UserEssence::where('user_id', $user->id)
            ->where('type', 'sector')
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNotNull($sectorEssence);
        $this->assertEquals(30, $sectorEssence->amount);

        // Check sector energy
        $sectorEnergy = SectorEnergy::where('user_id', $user->id)
            ->where('sector_id', $sector->id)
            ->first();
        $this->assertNotNull($sectorEnergy);
        $this->assertEquals(20, $sectorEnergy->current_energy);
    }

    /**
     * Test that rewards accumulate when granted multiple times
     */
    public function test_rewards_accumulate(): void
    {
        $user = User::factory()->create();

        $quest = Quest::create([
            'slug' => 'test_accumulate',
            'name' => 'Test Quest',
            'description' => 'Test',
            'type' => 'daily',
            'category' => 'scan',
            'target_value' => 1,
            'is_daily' => true,
            'is_active' => true,
            'reward_payload' => [
                ['type' => 'essence', 'essence_type' => 'generic', 'amount' => 25],
            ],
        ]);

        // Grant rewards twice
        $this->questRewardService->grantRewards($user, $quest);
        $this->questRewardService->grantRewards($user, $quest);

        $essence = UserEssence::where('user_id', $user->id)
            ->where('type', 'generic')
            ->whereNull('sector_id')
            ->first();

        $this->assertNotNull($essence);
        $this->assertEquals(50, $essence->amount); // 25 + 25
    }
}
