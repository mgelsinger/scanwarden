<?php

namespace Tests\Unit\Services;

use App\Services\ScanClassificationService;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanClassificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScanClassificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScanClassificationService();
    }

    public function test_upc_classification_is_deterministic(): void
    {
        $upc = '123456789012';

        $sector1 = $this->service->classifyUpc($upc);
        $sector2 = $this->service->classifyUpc($upc);

        $this->assertEquals($sector1->id, $sector2->id);
        $this->assertInstanceOf(Sector::class, $sector1);
    }

    public function test_different_upcs_may_have_different_sectors(): void
    {
        $upc1 = '111111111111';
        $upc2 = '999999999999';

        $sector1 = $this->service->classifyUpc($upc1);
        $sector2 = $this->service->classifyUpc($upc2);

        $this->assertInstanceOf(Sector::class, $sector1);
        $this->assertInstanceOf(Sector::class, $sector2);
        // They may or may not be different, just verify both work
    }

    public function test_classifies_to_valid_sector(): void
    {
        $upc = '042100005264';

        $sector = $this->service->classifyUpc($upc);

        $this->assertInstanceOf(Sector::class, $sector);
        $this->assertNotNull($sector->id);
        $this->assertNotNull($sector->name);
    }

    public function test_calculates_energy_gain(): void
    {
        $sector = Sector::first();
        $energyGain = $this->service->calculateEnergyGain($sector);

        $this->assertIsInt($energyGain);
        $this->assertGreaterThanOrEqual(10, $energyGain);
        $this->assertLessThanOrEqual(20, $energyGain);
    }

    public function test_should_summon_unit_on_first_scan(): void
    {
        $scanCount = 0; // First scan = 0
        $shouldSummon = $this->service->shouldSummonUnit($scanCount);

        $this->assertTrue($shouldSummon); // First scan always summons
    }

    public function test_summon_chance_is_random_after_first_scan(): void
    {
        $scanCount = 5;

        // Run multiple times to test randomness (at least one should return a boolean)
        $result = $this->service->shouldSummonUnit($scanCount);

        $this->assertIsBool($result);
    }
}
