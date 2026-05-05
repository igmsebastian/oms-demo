<?php

namespace Database\Factories;

use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Piece', 'Box', 'Case', 'Pack', 'Set']).' '.fake()->unique()->randomNumber(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'abbreviation' => str($name)->substr(0, 3)->upper()->toString(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
