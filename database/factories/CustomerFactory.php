<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Auth::hasUser() && Auth::user()->shop_id
                ? Auth::user()->shop_id
                : Shop::factory(),
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+263 77 ### ####'),
            'address' => fake()->city().', Zimbabwe',
        ];
    }
}
