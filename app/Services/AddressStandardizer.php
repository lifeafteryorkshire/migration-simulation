<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Http;

class AddressStandardizer
{
    protected string $baseUrl = 'https://api.os.uk/search/places/v1';

    /**
     * Standardize an address using OS Places API.
     * Accepts an Address model or raw attributes array.
     */
    public function standardize(Address|array $address): ?array
    {
        // Extract postcode whether passed an array or Eloquent Model
        if (is_array($address)) {
            $postcode = $address['Postcode'] ?? $address['postcode'] ?? $address['AddressLine1'] ?? null;
        } else {
            $postcode = $address->Postcode 
                ?? $address->postcode 
                ?? $address->getAttribute('Postcode') 
                ?? $address->getAttribute('postcode')
                ?? $address->getAttribute('AddressLine1');
        }

        if (empty($postcode)) {
            return null;
        }

        $apiKey = config('services.os_places.key', 'test_key');

        $response = Http::get("{$this->baseUrl}/postcode", [
            'postcode' => $postcode,
            'key'      => $apiKey,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $results = $response->json('results');

        if (empty($results) || !is_array($results)) {
            return null;
        }

        $firstMatch = $results[0] ?? [];
        $dpa = $firstMatch['DPA'] ?? $firstMatch['dpa'] ?? null;

        if (!$dpa) {
            return null;
        }

        return [
            'AddressLine1' => $dpa['ADDRESS'] ?? $dpa['address'] ?? null,
            'AddressLine2' => null,
            'TownCity'     => $dpa['POST_TOWN'] ?? $dpa['post_town'] ?? null,
            'County'       => null,
            'Postcode'     => $dpa['POSTCODE'] ?? $dpa['postcode'] ?? null,
            'UPRN'         => $dpa['UPRN'] ?? $dpa['uprn'] ?? null,
            'latitude'     => $dpa['LATITUDE'] ?? $dpa['latitude'] ?? null,
            'longitude'    => $dpa['LONGITUDE'] ?? $dpa['longitude'] ?? null,
            'easting'      => $dpa['X_COORDINATE'] ?? $dpa['x_coordinate'] ?? null,
            'northing'     => $dpa['Y_COORDINATE'] ?? $dpa['y_coordinate'] ?? null,
        ];
    }
}