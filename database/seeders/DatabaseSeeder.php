<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OrderStatusSeeder::class,
            AdminUserSeeder::class,
            CustomerUserSeeder::class,
            ProductSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
