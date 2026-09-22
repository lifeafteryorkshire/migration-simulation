<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'legacy_address_id' => $this->faker->unique()->numberBetween(1001, 9999),
            'uprn'              => (string) $this->faker->numberBetween(100000000000, 999999999999),
            'address_line_1'    => $this->faker->buildingNumber() . ' ' . strtoupper($this->faker->streetName()),
            'address_line_2'    => $this->faker->optional(0.3)->streetSuffix(),
            'town_city'         => strtoupper($this->faker->city()),
            'county'            => $this->faker->state(),
            'postcode'          => $this->faker->regexify('[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][A-Z]{2}'),
            'latitude'          => $this->faker->latitude(50.0, 58.0),
            'longitude'         => $this->faker->longitude(-7.5, 1.8),
            'easting'           => $this->faker->numberBetween(100000, 600000),
            'northing'          => $this->faker->numberBetween(100000, 1200000),
            'is_active'         => true,
        ];
    }
}