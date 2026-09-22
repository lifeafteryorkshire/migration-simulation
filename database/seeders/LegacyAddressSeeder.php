<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\LegacyAddress;

class LegacyAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = base_path('data/legacyaddresses.json');

        // Verify the file exists
        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found at: {$jsonPath}");
            return;
        }

        // Read and decode JSON file
        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        // Access export format uses "tbl_Addresses" key
        $records = $data['legacy_addresses'] ?? [];

        if (empty($records)) {
            $this->command->warn('No address records found in the JSON file.');
            return;
        }

        $this->command->info('Importing legacy Access address data...');

        // Process in chunks for memory efficiency
        $chunks = array_chunk($records, 50);

        foreach ($chunks as $chunk) {
            $insertData = [];

            foreach ($chunk as $record) {
                $insertData[] = [
                    'AddressID'    => $record['AddressID'],
                    'AddressLine1' => $record['AddressLine1'] ?? null,
                    'AddressLine2' => $record['AddressLine2'] ?? null,
                    'TownCity'     => $record['TownCity'] ?? null,
                    'County'       => $record['County'] ?? null,
                    'Postcode'     => $record['Postcode'] ?? null,
                    'DateCreated'  => $record['DateCreated'] ?? null,
                    'Active'       => $record['Active'] ?? true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            // Insert records while handling potential primary key collisions
            LegacyAddress::upsert(
                $insertData,
                ['AddressID'],
                ['AddressLine1', 'AddressLine2', 'TownCity', 'County', 'Postcode', 'DateCreated', 'Active', 'updated_at']
            );
        }

        $count = count($records);
        $this->command->info("Successfully imported {$count} legacy address records.");
    
    }
}
