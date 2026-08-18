<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1, 20);

        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'cost_price' => round($price * 0.7, 2),
            'selling_price' => $price,
            'unit_price' => $price,
            'tax_rate' => 0,
            'quantity' => 0,
            'min_stock' => 5,
            'unit' => 'items',
            'status' => 'active',
        ];
    }
}
