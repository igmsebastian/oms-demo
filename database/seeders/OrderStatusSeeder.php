<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\OrderStatusReference;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Seed the order status reference table from the enum source of truth.
     */
    public function run(): void
    {
        foreach (OrderStatus::cases() as $status) {
            OrderStatusReference::updateOrCreate(
                ['id' => $status->value],
                [
                    'name' => $status->nameValue(),
                    'label' => $status->label(),
                    'sort_order' => $status->value,
                    'is_active' => true,
                ],
            );
        }
    }
}
