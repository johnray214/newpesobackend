<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@peso.gov.ph'],
            [
                'first_name'  => 'PESO',
                'middle_name' => null,
                'last_name'   => 'Admin',
                'password'    => Hash::make('password123'),
                'role'        => 'admin',
                'sex'         => 'male',
                'contact'     => '09001234567',
                'address'     => 'Villasis, Santiago City, Isabela',
                'status'      => 'active',
            ]
        );

        // Staff
        User::updateOrCreate(
            ['email' => 'staff@peso.gov.ph'],
            [
                'first_name'  => 'Maria',
                'middle_name' => 'Santos',
                'last_name'   => 'Reyes',
                'password'    => Hash::make('password123'),
                'role'        => 'staff',
                'sex'         => 'female',
                'contact'     => '09109876543',
                'address'     => 'Quezon, Santiago City, Isabela',
                'status'      => 'active',
            ]
        );
    }
}
