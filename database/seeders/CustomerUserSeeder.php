<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerUserSeeder extends Seeder
{
    /**
     * Seed the default customer user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'first_name' => 'Customer',
                'middle_name' => null,
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        );
    }
}
