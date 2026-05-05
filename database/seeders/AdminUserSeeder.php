<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed realistic admin users for demo operations.
     */
    public function run(): void
    {
        User::query()
            ->whereIn('email', [
                'admin@example.com',
                'inventory.admin@example.com',
                'support.admin@example.com',
            ])
            ->delete();

        collect([
            [
                'first_name' => 'Basty',
                'middle_name' => null,
                'last_name' => 'Reyes',
                'email' => 'basty@mydemo.com',
            ],
            [
                'first_name' => 'Ethan',
                'middle_name' => null,
                'last_name' => 'Wells',
                'email' => 'ethan.wells@mydemo.com',
            ],
            [
                'first_name' => 'Sofia',
                'middle_name' => null,
                'last_name' => 'Nguyen',
                'email' => 'sofia.nguyen@mydemo.com',
            ],
        ])->each(fn (array $admin): User => User::updateOrCreate(
            ['email' => $admin['email']],
            [
                ...$admin,
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        ));
    }
}
