<?php

use App\Models\User;
use Database\Seeders\OrderStatusSeeder;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('api reports are admin only and return summary shapes', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->getJson('/api/reports')->assertUnauthorized();

    $this->actingAs($user)
        ->getJson('/api/reports')
        ->assertForbidden();

    $this->actingAs($admin)
        ->getJson('/api/reports')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'orders',
                'revenue' => ['gross_revenue', 'completed_revenue', 'refunded_revenue'],
                'inventory',
                'low_stock_count',
            ],
        ]);
});
