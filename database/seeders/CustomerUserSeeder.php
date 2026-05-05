<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerUserSeeder extends Seeder
{
    /**
     * Seed realistic customer users and shipping addresses.
     */
    public function run(): void
    {
        User::query()
            ->whereIn('email', [
                'customer@example.com',
                'emma.carter@example.com',
                'noah.bennett@example.com',
                'ava.patel@example.com',
                'lucas.kim@example.com',
                'mia.thompson@example.com',
                'jordan.rivera@example.com',
            ])
            ->delete();

        collect([
            [
                'first_name' => 'Emma',
                'middle_name' => null,
                'last_name' => 'Carter',
                'email' => 'emma.carter@mydemo.com',
                'address_line_1' => '418 Maple Avenue',
                'address_line_2' => 'Apt 5B',
                'city' => 'Brooklyn',
                'country' => 'United States',
                'post_code' => '11215',
            ],
            [
                'first_name' => 'Noah',
                'middle_name' => null,
                'last_name' => 'Bennett',
                'email' => 'noah.bennett@mydemo.com',
                'address_line_1' => '2217 North Damen Avenue',
                'address_line_2' => null,
                'city' => 'Chicago',
                'country' => 'United States',
                'post_code' => '60647',
            ],
            [
                'first_name' => 'Ava',
                'middle_name' => null,
                'last_name' => 'Patel',
                'email' => 'ava.patel@mydemo.com',
                'address_line_1' => '940 Valencia Street',
                'address_line_2' => null,
                'city' => 'San Francisco',
                'country' => 'United States',
                'post_code' => '94110',
            ],
            [
                'first_name' => 'Lucas',
                'middle_name' => null,
                'last_name' => 'Kim',
                'email' => 'lucas.kim@mydemo.com',
                'address_line_1' => '1720 East Pike Street',
                'address_line_2' => 'Suite 301',
                'city' => 'Seattle',
                'country' => 'United States',
                'post_code' => '98122',
            ],
            [
                'first_name' => 'Mia',
                'middle_name' => null,
                'last_name' => 'Thompson',
                'email' => 'mia.thompson@mydemo.com',
                'address_line_1' => '75 Piedmont Avenue NE',
                'address_line_2' => 'Unit 12',
                'city' => 'Atlanta',
                'country' => 'United States',
                'post_code' => '30303',
            ],
            [
                'first_name' => 'Jordan',
                'middle_name' => null,
                'last_name' => 'Rivera',
                'email' => 'jordan.rivera@mydemo.com',
                'address_line_1' => '522 South Spring Street',
                'address_line_2' => 'Loft 604',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'post_code' => '90013',
            ],
        ])->each(function (array $customer): void {
            $user = User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'first_name' => $customer['first_name'],
                    'middle_name' => $customer['middle_name'],
                    'last_name' => $customer['last_name'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::User,
                    'email_verified_at' => now(),
                ],
            );

            UserAddress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'address_line_1' => $customer['address_line_1'],
                ],
                [
                    'address_line_2' => $customer['address_line_2'],
                    'city' => $customer['city'],
                    'country' => $customer['country'],
                    'post_code' => $customer['post_code'],
                    'is_default' => true,
                ],
            );
        });
    }
}
