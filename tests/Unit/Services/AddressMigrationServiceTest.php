<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\LegacyAddress;
use App\Services\AddressMigrationService;
use App\Services\AddressStandardizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AddressMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_only_processes_unmigrated_records(): void
    {
        // 1. Seed 1 migrated and 1 unmigrated legacy address
        LegacyAddress::factory()->create([
            'AddressLine1' => '10 Downing Street',
            'Postcode'     => 'SW1A 2AA',
            'migrated'     => true,
        ]);

        $unmigrated = LegacyAddress::factory()->create([
            'AddressLine1' => '221B Baker Street',
            'Postcode'     => 'NW1 6XE',
            'migrated'     => false,
        ]);

        // 2. Mock standardizer service
        $standardizerMock = Mockery::mock(AddressStandardizer::class);
        $standardizerMock->shouldReceive('standardize')
            ->once()
            ->andReturn([
                'AddressLine1' => '221B BAKER STREET',
                'AddressLine2' => null,
                'TownCity'     => 'LONDON',
                'County'       => null,
                'Postcode'     => 'NW1 6XE',
                'UPRN'         => '100023336956',
                'latitude'     => 51.5237,
                'longitude'    => -0.1585,
                'easting'      => 527920,
                'northing'     => 182180,
            ]);

        $service = new AddressMigrationService($standardizerMock);
        $report = $service->migratePending();

        // 3. Assertions
        $this->assertEquals(1, $report['total_pending']);
        $this->assertEquals(1, $report['successful']);
        $this->assertEquals(0, $report['failed']);

        // Check primary key dynamically via getKeyName() / getKey()
        $this->assertDatabaseHas('legacy_addresses', [
            $unmigrated->getKeyName() => $unmigrated->getKey(),
            'migrated'                => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'legacy_address_id' => $unmigrated->getKey(),
            'address_line_1'    => '221B BAKER STREET',
            'postcode'          => 'NW1 6XE',
            'uprn'              => '100023336956',
        ]);
    }

    public function test_it_continues_execution_when_a_record_fails(): void
    {
        $recordFails = LegacyAddress::factory()->create([
            'AddressLine1' => 'Invalid Address',
            'Postcode'     => 'XX1 1XX',
            'migrated'     => false,
        ]);

        $recordSucceeds = LegacyAddress::factory()->create([
            'AddressLine1' => '10 Downing Street',
            'Postcode'     => 'SW1A 2AA',
            'migrated'     => false,
        ]);

        $standardizerMock = Mockery::mock(AddressStandardizer::class);

        // Explicitly match by Postcode / AddressLine1
        $standardizerMock->shouldReceive('standardize')
            ->with(Mockery::on(fn ($data) => ($data['Postcode'] ?? $data['postcode'] ?? null) === 'XX1 1XX'))
            ->once()
            ->andReturn(null);

        $standardizerMock->shouldReceive('standardize')
            ->with(Mockery::on(fn ($data) => ($data['Postcode'] ?? $data['postcode'] ?? null) === 'SW1A 2AA'))
            ->once()
            ->andReturn([
                'AddressLine1' => '10 DOWNING STREET',
                'TownCity'     => 'LONDON',
                'Postcode'     => 'SW1A 2AA',
                'UPRN'         => '100023336956',
            ]);

        $service = new AddressMigrationService($standardizerMock);
        $report = $service->migratePending();

        $this->assertEquals(2, $report['total_pending']);
        $this->assertEquals(1, $report['successful']);
        $this->assertEquals(1, $report['failed']);

        // Failed record remains unmigrated
        $this->assertDatabaseHas('legacy_addresses', [
            $recordFails->getKeyName() => $recordFails->getKey(),
            'migrated'                 => 0,
        ]);

        // Successful record is marked as migrated
        $this->assertDatabaseHas('legacy_addresses', [
            $recordSucceeds->getKeyName() => $recordSucceeds->getKey(),
            'migrated'                    => 1,
        ]);
    }
}
