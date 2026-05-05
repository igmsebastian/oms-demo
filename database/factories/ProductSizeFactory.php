<?php

namespace Database\Factories;

use App\Models\ProductSize;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductSize>
 */
class ProductSizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Small', 'Medium', 'Large', 'Extra Large']).' '.fake()->unique()->randomNumber(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => str($name)->substr(0, 4)->upper()->toString(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
