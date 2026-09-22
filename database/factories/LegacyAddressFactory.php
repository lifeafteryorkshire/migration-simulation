<?php

namespace Database\Factories;

use App\Models\LegacyAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegacyAddress>
 */
class LegacyAddressFactory extends Factory
{
    protected $model = LegacyAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'AddressID'    => $this->faker->unique()->numberBetween(1001, 9999),
            'AddressLine1' => $this->faker->buildingNumber() . ' ' . $this->faker->streetName(),
            'AddressLine2' => $this->faker->optional(0.4)->streetSuffix(),
            'TownCity'     => $this->faker->city(),
            'County'       => $this->faker->state(),
            'Postcode'     => $this->faker->regexify('[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][A-Z]{2}'),
            'DateCreated'  => $this->faker->dateTimeBetween('2010-01-01', '2010-12-31')->format('Y-m-d H:i:s'),
            'Active'       => true,
        ];
    }
}
