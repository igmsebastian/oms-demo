<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    /**
     * Seed one simple pending order for manual review.
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@example.com')->first();
        $product = Product::where('sku', 'SKU-1001')->first();

        if (! $customer || ! $product) {
            return;
        }

        $address = UserAddress::updateOrCreate(
            [
                'user_id' => $customer->id,
                'address_line_1' => '100 Demo Street',
            ],
            [
                'address_line_2' => null,
                'city' => 'Demo City',
                'country' => 'United States',
                'post_code' => '10001',
                'is_default' => true,
            ],
        );

        if (Order::where('user_id', $customer->id)->exists()) {
            return;
        }

        $order = Order::create([
            'user_id' => $customer->id,
            'user_address_id' => $address->id,
            'status' => OrderStatus::Pending,
            'total_amount' => $product->price,
            'shipping_address_line_1' => $address->address_line_1,
            'shipping_address_line_2' => $address->address_line_2,
            'shipping_city' => $address->city,
            'shipping_country' => $address->country,
            'shipping_post_code' => $address->post_code,
            'shipping_full_address' => $address->full_address,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => $product->price,
            'line_total' => $product->price,
        ]);
    }
}
