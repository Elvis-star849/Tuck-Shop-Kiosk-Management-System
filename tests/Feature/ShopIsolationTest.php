<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_cannot_see_another_shops_products(): void
    {
        $shopA = Shop::factory()->create(['name' => 'Shop A']);
        $shopB = Shop::factory()->create(['name' => 'Shop B']);

        $cashierA = User::factory()->create([
            'shop_id' => $shopA->id,
            'role' => 'cashier',
        ]);
        User::factory()->create([
            'shop_id' => $shopB->id,
            'role' => 'cashier',
        ]);

        $visible = Product::factory()->create([
            'shop_id' => $shopA->id,
            'name' => 'Shop A Bread',
        ]);
        $hidden = Product::factory()->create([
            'shop_id' => $shopB->id,
            'name' => 'Shop B Coke',
        ]);

        $this->actingAs($cashierA);

        $products = Product::query()->pluck('id');

        $this->assertTrue($products->contains($visible->id));
        $this->assertFalse($products->contains($hidden->id));
    }
}
