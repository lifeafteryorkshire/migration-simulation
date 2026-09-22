<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserLegacyAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all()->pluck('id')->toArray();
        $addresses = \App\Models\LegacyAddress::all()->pluck('AddressID')->toArray();

        $userAddresses = [];

        foreach ($users as $idx =>$user) {
            $userAddresses = array_merge($userAddresses, array_map(function ($address) use ($user) {
                return [
                    'user_id' => $user,
                    'address_id' => $address,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $addresses));
        }
        
        \App\Models\UserLegacyAddress::insert($userAddresses);
    }
}
