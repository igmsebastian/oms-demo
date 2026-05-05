<?php

namespace Database\Factories;

use App\Models\ProductTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductTag>
 */
class ProductTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => str($name)->headline()->toString(),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
