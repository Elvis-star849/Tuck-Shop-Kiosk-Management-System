<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Tuck Shop',
            'phone' => fake()->numerify('+263 77 ### ####'),
            'address' => fake()->city().', Zimbabwe',
            'status' => 'active',
        ];
    }
}
