<?php

namespace Tests\Feature;

use App\Models\LegacyAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressMigrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_endpoint_processes_unmigrated_addresses_and_returns_summary(): void
    {
        // 1. Global Http fake matching any outbound call
        Http::fake([
            '*' => Http::response([
                'results' => [
                    [
                        'DPA' => [
                            'UPRN'              => '100023336956',
                            'ADDRESS'           => '10 DOWNING STREET',
                            'POST_TOWN'          => 'LONDON',
                            'POSTCODE'          => 'SW1A 2AA',
                            'LATITUDE'          => 51.503363,
                            'LONGITUDE'         => -0.127625,
                            'X_COORDINATE'      => 530047,
                            'Y_COORDINATE'      => 179951,
                        ],
                    ],
                ],
            ], 200),
        ]);

        // 2. Create legacy address with explicit key definitions
        $legacy = LegacyAddress::factory()->create([
            'AddressLine1' => '10 Downing Street',
            'Postcode'     => 'SW1A 2AA',
            'migrated'     => false,
        ]);

        // 3. Perform request
        $response = $this->postJson('/api/addresses/migrate');

        // 4. Assert response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pending legacy address migration batch completed.',
                'summary' => [
                    'total_pending' => 1,
                    'successful'    => 1,
                    'failed'        => 0,
                ],
            ]);

        // 5. Database assertions using Eloquent key getters
        $this->assertDatabaseHas('legacy_addresses', [
            $legacy->getKeyName() => $legacy->getKey(),
            'migrated'            => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'legacy_address_id' => $legacy->getKey(),
            'uprn'              => '100023336956',
        ]);
    }

    public function test_migration_endpoint_returns_multi_status_when_some_records_fail(): void
    {
        Http::fake([
            '*' => Http::response(['results' => []], 200),
        ]);

        LegacyAddress::factory()->create([
            'AddressLine1' => 'Unknown Place',
            'Postcode'     => 'ZZ9 9ZZ',
            'migrated'     => false,
        ]);

        $response = $this->postJson('/api/addresses/migrate');

        $response->assertStatus(207)
            ->assertJson([
                'summary' => [
                    'total_pending' => 1,
                    'successful'    => 0,
                    'failed'        => 1,
                ],
            ]);
    }
}