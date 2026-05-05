<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use App\Services\ProductTaxonomyService;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed realistic sneaker product management data and inventory.
     */
    public function run(): void
    {
        $this->removeLegacyWarehouseDemoProducts();

        $categories = $this->seedTaxonomy(ProductCategory::class, [
            ['name' => 'Running Shoes', 'description' => 'Road running shoes, race-day trainers, and daily mileage sneakers.'],
            ['name' => 'Basketball Sneakers', 'description' => 'Court-ready mids, highs, and supportive performance sneakers.'],
            ['name' => 'Lifestyle Sneakers', 'description' => 'Everyday sneakers, retro silhouettes, and streetwear staples.'],
            ['name' => 'Training Shoes', 'description' => 'Gym, HIIT, and cross-training footwear.'],
            ['name' => 'Trail Sneakers', 'description' => 'Outdoor sneakers with grip, protection, and water-resistant builds.'],
            ['name' => 'Limited Editions', 'description' => 'Small-batch collaborations, numbered drops, and collector releases.'],
        ]);

        $brands = $this->seedTaxonomy(ProductBrand::class, [
            ['name' => 'StrideLab', 'description' => 'Lightweight running footwear with responsive midsoles.'],
            ['name' => 'Northcourt', 'description' => 'Basketball-first sneakers built for support and impact control.'],
            ['name' => 'Urban Sole', 'description' => 'Lifestyle sneakers inspired by city commuting and streetwear.'],
            ['name' => 'Apex Runner', 'description' => 'Performance footwear focused on speed, cushioning, and fit.'],
            ['name' => 'TrailForge', 'description' => 'Rugged outdoor sneakers for mixed terrain.'],
            ['name' => 'Heritage Co.', 'description' => 'Classic low tops, canvas sneakers, and retro suede designs.'],
            ['name' => 'Nimbus Athletics', 'description' => 'Training shoes for gym, studio, and everyday workouts.'],
            ['name' => 'Courtline', 'description' => 'Limited court-inspired releases and premium collector pairs.'],
        ]);

        $units = $this->seedTaxonomy(ProductUnit::class, [
            ['name' => 'Pair', 'slug' => 'pair', 'abbreviation' => 'pr', 'description' => 'One left shoe and one right shoe.'],
            ['name' => 'Box', 'slug' => 'box', 'abbreviation' => 'box', 'description' => 'Retail shoebox packaging unit.'],
        ]);

        $sizes = $this->seedTaxonomy(ProductSize::class, [
            ['name' => 'US 7', 'slug' => 'us-7', 'code' => 'US7', 'description' => 'Adult US size 7.'],
            ['name' => 'US 8', 'slug' => 'us-8', 'code' => 'US8', 'description' => 'Adult US size 8.'],
            ['name' => 'US 9', 'slug' => 'us-9', 'code' => 'US9', 'description' => 'Adult US size 9.'],
            ['name' => 'US 10', 'slug' => 'us-10', 'code' => 'US10', 'description' => 'Adult US size 10.'],
            ['name' => 'US 11', 'slug' => 'us-11', 'code' => 'US11', 'description' => 'Adult US size 11.'],
            ['name' => 'US 12', 'slug' => 'us-12', 'code' => 'US12', 'description' => 'Adult US size 12.'],
        ]);

        $colors = $this->seedTaxonomy(ProductColor::class, [
            ['name' => 'Triple White', 'slug' => 'triple-white', 'hex_code' => '#f8fafc', 'description' => 'Clean white upper with tonal detailing.'],
            ['name' => 'Black and White', 'slug' => 'black-and-white', 'hex_code' => '#111827', 'description' => 'Black base with white contrast details.'],
            ['name' => 'University Red', 'slug' => 'university-red', 'hex_code' => '#dc2626', 'description' => 'Bold red accents inspired by varsity colorways.'],
            ['name' => 'Sail Gum', 'slug' => 'sail-gum', 'hex_code' => '#d6c3a1', 'description' => 'Sail upper with classic gum outsole.'],
            ['name' => 'Volt Lime', 'slug' => 'volt-lime', 'hex_code' => '#a3e635', 'description' => 'High-visibility lime training colorway.'],
            ['name' => 'Concrete Grey', 'slug' => 'concrete-grey', 'hex_code' => '#6b7280', 'description' => 'Neutral grey upper for everyday wear.'],
            ['name' => 'Royal Blue', 'slug' => 'royal-blue', 'hex_code' => '#2563eb', 'description' => 'Blue upper with crisp contrast.'],
            ['name' => 'Mocha Brown', 'slug' => 'mocha-brown', 'hex_code' => '#7c2d12', 'description' => 'Earth-toned suede and nubuck mix.'],
        ]);

        $tags = $this->seedTaxonomy(ProductTag::class, [
            ['name' => 'Best Seller', 'slug' => 'best-seller', 'color' => '#16a34a'],
            ['name' => 'Limited Edition', 'slug' => 'limited-edition', 'color' => '#7c3aed'],
            ['name' => 'New Arrival', 'slug' => 'new-arrival', 'color' => '#2563eb'],
            ['name' => 'Restock Soon', 'slug' => 'restock-soon', 'color' => '#f59e0b'],
            ['name' => 'Performance', 'slug' => 'performance', 'color' => '#0891b2'],
            ['name' => 'Retro', 'slug' => 'retro', 'color' => '#db2777'],
            ['name' => 'Collaboration', 'slug' => 'collaboration', 'color' => '#9333ea'],
            ['name' => 'Low Stock', 'slug' => 'low-stock', 'color' => '#dc2626'],
            ['name' => 'Vegan Materials', 'slug' => 'vegan-materials', 'color' => '#15803d'],
        ]);

        $this->seedProducts($categories, $brands, $units, $sizes, $colors, $tags);

        app(ProductTaxonomyService::class)->invalidate();
        app(ReportService::class)->invalidate();
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, Model>
     */
    protected function seedTaxonomy(string $model, array $records): array
    {
        return collect($records)
            ->mapWithKeys(function (array $record) use ($model): array {
                $name = $record['name'];
                $taxonomy = $model::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        ...$record,
                        'slug' => $record['slug'] ?? Str::slug($name),
                        'is_active' => $record['is_active'] ?? true,
                    ],
                );

                return [$name => $taxonomy];
            })
            ->all();
    }

    /**
     * @param  array<string, ProductCategory>  $categories
     * @param  array<string, ProductBrand>  $brands
     * @param  array<string, ProductUnit>  $units
     * @param  array<string, ProductSize>  $sizes
     * @param  array<string, ProductColor>  $colors
     * @param  array<string, ProductTag>  $tags
     */
    protected function seedProducts(
        array $categories,
        array $brands,
        array $units,
        array $sizes,
        array $colors,
        array $tags,
    ): void {
        collect($this->products())->each(function (array $record) use ($categories, $brands, $units, $sizes, $colors, $tags): void {
            $product = Product::updateOrCreate(
                ['sku' => $record['sku']],
                [
                    'name' => $record['name'],
                    'description' => $record['description'],
                    'product_category_id' => $categories[$record['category']]->id,
                    'product_brand_id' => $brands[$record['brand']]->id,
                    'product_unit_id' => $units[$record['unit']]->id,
                    'product_size_id' => $sizes[$record['size']]->id,
                    'product_color_id' => $colors[$record['color']]->id,
                    'price' => $record['price'],
                    'stock_quantity' => $record['stock_quantity'],
                    'low_stock_threshold' => $record['low_stock_threshold'],
                    'is_active' => $record['is_active'] ?? true,
                ],
            );

            $product->tags()->sync(
                collect($record['tags'])
                    ->map(fn (string $tag): string => $tags[$tag]->id)
                    ->all(),
            );
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function products(): array
    {
        return [
            [
                'sku' => 'SNEAK-RUN-001',
                'name' => 'StrideLab AeroRun 3 Road Runner',
                'description' => 'Daily running sneaker with a breathable engineered mesh upper and responsive foam midsole.',
                'category' => 'Running Shoes',
                'brand' => 'StrideLab',
                'unit' => 'Pair',
                'size' => 'US 10',
                'color' => 'Royal Blue',
                'price' => 138.00,
                'stock_quantity' => 42,
                'low_stock_threshold' => 8,
                'tags' => ['Best Seller', 'Performance'],
            ],
            [
                'sku' => 'SNEAK-RUN-002',
                'name' => 'Apex Runner CloudFlex Knit',
                'description' => 'Lightweight neutral trainer with a sock-like collar and soft heel lockdown.',
                'category' => 'Running Shoes',
                'brand' => 'Apex Runner',
                'unit' => 'Pair',
                'size' => 'US 9',
                'color' => 'Triple White',
                'price' => 124.00,
                'stock_quantity' => 18,
                'low_stock_threshold' => 5,
                'tags' => ['New Arrival', 'Performance', 'Vegan Materials'],
            ],
            [
                'sku' => 'SNEAK-RUN-003',
                'name' => 'StrideLab Marathon Elite Carbon',
                'description' => 'Race-day sneaker with a plated midsole, rocker geometry, and lightweight woven upper.',
                'category' => 'Running Shoes',
                'brand' => 'StrideLab',
                'unit' => 'Pair',
                'size' => 'US 11',
                'color' => 'Volt Lime',
                'price' => 219.00,
                'stock_quantity' => 4,
                'low_stock_threshold' => 8,
                'tags' => ['Performance', 'Low Stock'],
            ],
            [
                'sku' => 'SNEAK-RUN-004',
                'name' => 'Apex Runner Tempo Glide',
                'description' => 'Tempo training sneaker with a stable platform for intervals and longer progression runs.',
                'category' => 'Running Shoes',
                'brand' => 'Apex Runner',
                'unit' => 'Pair',
                'size' => 'US 8',
                'color' => 'Black and White',
                'price' => 149.00,
                'stock_quantity' => 63,
                'low_stock_threshold' => 12,
                'tags' => ['Best Seller', 'Performance'],
            ],
            [
                'sku' => 'SNEAK-BSK-001',
                'name' => 'Northcourt Pivot Pro High',
                'description' => 'High-top basketball sneaker with ankle support, herringbone traction, and impact cushioning.',
                'category' => 'Basketball Sneakers',
                'brand' => 'Northcourt',
                'unit' => 'Pair',
                'size' => 'US 11',
                'color' => 'University Red',
                'price' => 168.00,
                'stock_quantity' => 27,
                'low_stock_threshold' => 6,
                'tags' => ['Performance', 'Best Seller'],
            ],
            [
                'sku' => 'SNEAK-BSK-002',
                'name' => 'Northcourt Baseline Classic Mid',
                'description' => 'Retro basketball mid with a padded collar, leather overlays, and a durable cupsole.',
                'category' => 'Basketball Sneakers',
                'brand' => 'Northcourt',
                'unit' => 'Pair',
                'size' => 'US 12',
                'color' => 'Black and White',
                'price' => 132.00,
                'stock_quantity' => 0,
                'low_stock_threshold' => 4,
                'tags' => ['Retro', 'Restock Soon'],
            ],
            [
                'sku' => 'SNEAK-LFS-001',
                'name' => 'Urban Sole Metro 90 Retro Low',
                'description' => 'Low-profile lifestyle sneaker with paneled suede, smooth leather, and a cushioned insole.',
                'category' => 'Lifestyle Sneakers',
                'brand' => 'Urban Sole',
                'unit' => 'Pair',
                'size' => 'US 9',
                'color' => 'Sail Gum',
                'price' => 112.00,
                'stock_quantity' => 56,
                'low_stock_threshold' => 10,
                'tags' => ['Best Seller', 'Retro'],
            ],
            [
                'sku' => 'SNEAK-LFS-002',
                'name' => 'Heritage Co. Canvas 70 Low',
                'description' => 'Classic canvas low-top with reinforced eyelets, vulcanized sole, and vintage foxing stripe.',
                'category' => 'Lifestyle Sneakers',
                'brand' => 'Heritage Co.',
                'unit' => 'Pair',
                'size' => 'US 8',
                'color' => 'Triple White',
                'price' => 74.00,
                'stock_quantity' => 9,
                'low_stock_threshold' => 12,
                'tags' => ['Retro', 'Low Stock'],
            ],
            [
                'sku' => 'SNEAK-LFS-003',
                'name' => 'Urban Sole Gallery Edition Low',
                'description' => 'Numbered lifestyle release with premium nubuck, waxed laces, and tonal embroidery.',
                'category' => 'Limited Editions',
                'brand' => 'Urban Sole',
                'unit' => 'Pair',
                'size' => 'US 10',
                'color' => 'Mocha Brown',
                'price' => 188.00,
                'stock_quantity' => 0,
                'low_stock_threshold' => 5,
                'tags' => ['Limited Edition', 'Collaboration', 'Restock Soon'],
            ],
            [
                'sku' => 'SNEAK-TRN-001',
                'name' => 'Nimbus Athletics Flex Gym Trainer',
                'description' => 'Stable training shoe with a wide base for lifts, rope texture wrap, and flexible forefoot.',
                'category' => 'Training Shoes',
                'brand' => 'Nimbus Athletics',
                'unit' => 'Pair',
                'size' => 'US 10',
                'color' => 'Concrete Grey',
                'price' => 119.00,
                'stock_quantity' => 34,
                'low_stock_threshold' => 7,
                'tags' => ['Performance', 'New Arrival'],
            ],
            [
                'sku' => 'SNEAK-TRN-002',
                'name' => 'Nimbus Athletics Studio Lift Trainer',
                'description' => 'Low-drop trainer with a grippy outsole and reinforced heel for strength sessions.',
                'category' => 'Training Shoes',
                'brand' => 'Nimbus Athletics',
                'unit' => 'Pair',
                'size' => 'US 7',
                'color' => 'Volt Lime',
                'price' => 129.00,
                'stock_quantity' => 3,
                'low_stock_threshold' => 6,
                'tags' => ['Performance', 'Low Stock'],
            ],
            [
                'sku' => 'SNEAK-TRL-001',
                'name' => 'TrailForge Ridge Waterproof Low',
                'description' => 'Trail sneaker with a lugged outsole, guarded toe cap, and water-resistant ripstop upper.',
                'category' => 'Trail Sneakers',
                'brand' => 'TrailForge',
                'unit' => 'Pair',
                'size' => 'US 11',
                'color' => 'Concrete Grey',
                'price' => 154.00,
                'stock_quantity' => 21,
                'low_stock_threshold' => 5,
                'tags' => ['Performance', 'New Arrival'],
            ],
            [
                'sku' => 'SNEAK-TRL-002',
                'name' => 'TrailForge Summit Grip Low',
                'description' => 'Light trail sneaker designed for fast hikes with rock plate protection and heel pull tab.',
                'category' => 'Trail Sneakers',
                'brand' => 'TrailForge',
                'unit' => 'Pair',
                'size' => 'US 9',
                'color' => 'Mocha Brown',
                'price' => 142.00,
                'stock_quantity' => 2,
                'low_stock_threshold' => 5,
                'tags' => ['Performance', 'Low Stock'],
            ],
            [
                'sku' => 'SNEAK-LTD-001',
                'name' => 'Courtline Draft Night Limited High',
                'description' => 'Collector high-top with pebbled leather, metallic lace lock, and numbered tongue label.',
                'category' => 'Limited Editions',
                'brand' => 'Courtline',
                'unit' => 'Pair',
                'size' => 'US 10',
                'color' => 'University Red',
                'price' => 245.00,
                'stock_quantity' => 0,
                'low_stock_threshold' => 3,
                'tags' => ['Limited Edition', 'Collaboration', 'Restock Soon'],
            ],
            [
                'sku' => 'SNEAK-LTD-002',
                'name' => 'Courtline All-Star Weekend LE',
                'description' => 'Limited court sneaker with patent overlays, stitched star details, and premium packaging.',
                'category' => 'Limited Editions',
                'brand' => 'Courtline',
                'unit' => 'Pair',
                'size' => 'US 12',
                'color' => 'Royal Blue',
                'price' => 265.00,
                'stock_quantity' => 1,
                'low_stock_threshold' => 4,
                'tags' => ['Limited Edition', 'Low Stock'],
            ],
            [
                'sku' => 'SNEAK-LTD-003',
                'name' => 'Urban Sole Artist Series Runner',
                'description' => 'Small-batch runner with gallery-inspired color blocking and a numbered hangtag.',
                'category' => 'Limited Editions',
                'brand' => 'Urban Sole',
                'unit' => 'Pair',
                'size' => 'US 9',
                'color' => 'Royal Blue',
                'price' => 198.00,
                'stock_quantity' => 12,
                'low_stock_threshold' => 2,
                'tags' => ['Limited Edition', 'Collaboration'],
            ],
            [
                'sku' => 'SNEAK-RET-001',
                'name' => 'Heritage Co. Suede Campus Low',
                'description' => 'Retro suede low-top with leather side stripes and a classic gum rubber outsole.',
                'category' => 'Lifestyle Sneakers',
                'brand' => 'Heritage Co.',
                'unit' => 'Pair',
                'size' => 'US 10',
                'color' => 'Mocha Brown',
                'price' => 96.00,
                'stock_quantity' => 48,
                'low_stock_threshold' => 10,
                'tags' => ['Retro', 'Best Seller'],
            ],
            [
                'sku' => 'SNEAK-RET-002',
                'name' => 'Heritage Co. Court Archive 82',
                'description' => 'Archive tennis sneaker with full-grain leather, perforated toe box, and aged midsole.',
                'category' => 'Lifestyle Sneakers',
                'brand' => 'Heritage Co.',
                'unit' => 'Pair',
                'size' => 'US 11',
                'color' => 'Sail Gum',
                'price' => 108.00,
                'stock_quantity' => 5,
                'low_stock_threshold' => 8,
                'tags' => ['Retro', 'Low Stock'],
            ],
        ];
    }

    protected function removeLegacyWarehouseDemoProducts(): void
    {
        Product::query()
            ->whereIn('sku', ['SKU-1001', 'SKU-1002', 'SKU-1003'])
            ->whereIn('name', [
                'Wireless Barcode Scanner',
                'Thermal Shipping Labels',
                'Inventory Tote',
            ])
            ->delete();
    }
}
