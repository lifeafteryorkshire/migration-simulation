<?php

namespace Database\Factories;

use App\Models\UserLegacyAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLegacyAddress>
 */
class UserLegacyAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 100), // Assuming you have 100 users
            'address_id' => $this->faker->numberBetween(1001, 9999), // Assuming AddressID ranges from 1001 to 9999
        ];
    }
}
