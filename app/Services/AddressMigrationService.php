<?php

namespace App\Services;

use App\Models\Address;
use App\Models\LegacyAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AddressMigrationService
{
    public function __construct(
        protected AddressStandardizer $standardizer
    ) {}

    public function migratePending(): array
    {
        $unmigratedAddresses = LegacyAddress::unmigrated()->get();

        $stats = [
            'total_pending' => $unmigratedAddresses->count(),
            'successful'    => 0,
            'failed'        => 0,
            'results'       => [],
        ];

        foreach ($unmigratedAddresses as $legacy) {
            $result = $this->migrateSingleRecord($legacy);

            if ($result['status'] === 'success') {
                $stats['successful']++;
            } else {
                $stats['failed']++;
            }

            $stats['results'][] = $result;
        }

        return $stats;
    }

    protected function migrateSingleRecord(LegacyAddress $legacy): array
    {
        $legacyId = $legacy->getKey();

        try {
            // Extract raw attributes directly to prevent Eloquent attribute stripping
            $attributes = $legacy->toArray();

            $inputData = [
                'AddressLine1' => $attributes['AddressLine1'] ?? $attributes['address_line_1'] ?? $legacy->AddressLine1 ?? $legacy->address_line_1,
                'TownCity'     => $attributes['TownCity'] ?? $attributes['town_city'] ?? $legacy->TownCity ?? $legacy->town_city,
                'County'       => $attributes['County'] ?? $attributes['county'] ?? $legacy->County ?? $legacy->county,
                'Postcode'     => $attributes['Postcode'] ?? $attributes['postcode'] ?? $legacy->Postcode ?? $legacy->postcode,
            ];

            $cleaned = $this->standardizer->standardize($inputData);

            if (!$cleaned) {
                return [
                    'legacy_id' => $legacyId,
                    'status'    => 'failed',
                    'reason'    => 'No match found from Ordnance Survey Places API.',
                ];
            }

            $standardizedAddress = DB::transaction(function () use ($legacy, $cleaned, $legacyId) {
                $created = Address::updateOrCreate(
                    ['legacy_address_id' => $legacyId],
                    [
                        'uprn'           => $cleaned['UPRN'] ?? null,
                        'address_line_1' => $cleaned['AddressLine1'] ?? null,
                        'address_line_2' => $cleaned['AddressLine2'] ?? null,
                        'town_city'      => $cleaned['TownCity'] ?? null,
                        'county'         => $cleaned['County'] ?? null,
                        'postcode'       => $cleaned['Postcode'] ?? null,
                        'latitude'       => $cleaned['latitude'] ?? null,
                        'longitude'      => $cleaned['longitude'] ?? null,
                        'easting'        => $cleaned['easting'] ?? null,
                        'northing'       => $cleaned['northing'] ?? null,
                        'is_active'      => true,
                    ]
                );

                $legacy->update(['migrated' => true]);

                return $created;
            });

            return [
                'legacy_id' => $legacyId,
                'status'    => 'success',
                'address'   => [
                    'id'             => $standardizedAddress->id,
                    'uprn'           => $standardizedAddress->uprn,
                    'address_line_1' => $standardizedAddress->address_line_1,
                    'postcode'       => $standardizedAddress->postcode,
                ],
            ];
        } catch (Throwable $e) {
            Log::error("Migration failed for legacy address ID: {$legacyId}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'legacy_id' => $legacyId,
                'status'    => 'failed',
                'reason'    => $e->getMessage(),
            ];
        }
    }
}