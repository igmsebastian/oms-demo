<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed demo products for local development.
     */
    public function run(): void
    {
        collect([
            [
                'sku' => 'SKU-1001',
                'name' => 'Wireless Barcode Scanner',
                'description' => 'Bluetooth handheld scanner for warehouse order processing.',
                'price' => 129.00,
                'stock_quantity' => 25,
                'low_stock_threshold' => 5,
                'is_active' => true,
            ],
            [
                'sku' => 'SKU-1002',
                'name' => 'Thermal Shipping Labels',
                'description' => 'Four-by-six adhesive labels for outbound parcels.',
                'price' => 18.50,
                'stock_quantity' => 120,
                'low_stock_threshold' => 20,
                'is_active' => true,
            ],
            [
                'sku' => 'SKU-1003',
                'name' => 'Inventory Tote',
                'description' => 'Stackable storage tote for picking and packing workflows.',
                'price' => 14.75,
                'stock_quantity' => 8,
                'low_stock_threshold' => 10,
                'is_active' => true,
            ],
        ])->each(fn (array $product): Product => Product::updateOrCreate(
            ['sku' => $product['sku']],
            $product,
        ));
    }
}
