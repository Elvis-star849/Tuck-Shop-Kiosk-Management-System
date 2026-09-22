<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_receipt_number_is_rejected(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->get(route('returns.sale', ['number' => 'SALE-99999']))
            ->assertRedirect(route('returns.index'))
            ->assertSessionHas('error');
    }

    public function test_same_day_cash_return_restores_stock_and_records_refund(): void
    {
        $cashier = $this->cashier();
        $product = $this->product($cashier, 10, 1.00);
        $sale = $this->completeCashSale($cashier, $product, 10);

        $this->actingAs($cashier)
            ->post(route('returns.store'), $this->returnPayload($sale, 2))
            ->assertRedirect(route('sales.show', $sale));

        $product->refresh();
        $item = $sale->items()->first();
        $return = SaleReturn::query()->first();

        $this->assertSame('approved', $return->status);
        $this->assertEquals(2.0, (float) $item->fresh()->quantity_returned);
        $this->assertEquals(2.0, (float) $product->quantity);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'sale_return_id' => $return->id,
            'type' => 'refund',
            'amount' => 2.00,
        ]);
    }

    public function test_ecocash_return_is_queued_until_admin_approves(): void
    {
        $shop = Shop::factory()->create();
        $cashier = User::factory()->create(['shop_id' => $shop->id, 'role' => 'cashier']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $product = $this->product($cashier, 10, 1.00);
        $sale = $this->makeSale($cashier, $product, 10, ['payment_method' => 'ecocash']);

        $this->actingAs($cashier)
            ->post(route('returns.store'), $this->returnPayload($sale, 2))
            ->assertRedirect(route('returns.index'));

        $return = SaleReturn::query()->first();
        $this->assertSame('requested', $return->status);
        $this->assertEquals(0.0, (float) $product->fresh()->quantity);
        $this->assertEquals(0.0, (float) $sale->items()->first()->fresh()->quantity_returned);

        $this->actingAs($cashier)
            ->post(route('returns.approve', $return))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('returns.approve', $return))
            ->assertRedirect(route('returns.index'));

        $this->assertSame('approved', $return->fresh()->status);
        $this->assertEquals(2.0, (float) $product->fresh()->quantity);
        $this->assertEquals(2.0, (float) $sale->items()->first()->fresh()->quantity_returned);
    }

    public function test_cannot_return_more_than_sold(): void
    {
        $cashier = $this->cashier();
        $product = $this->product($cashier, 10, 1.00);
        $sale = $this->completeCashSale($cashier, $product, 10);

        $this->actingAs($cashier)
            ->from(route('returns.sale', ['number' => $sale->sale_number]))
            ->post(route('returns.store'), $this->returnPayload($sale, 11))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(0, SaleReturn::query()->count());
        $this->assertEquals(0.0, (float) $product->fresh()->quantity);
    }

    public function test_second_return_cannot_exceed_remaining_quantity(): void
    {
        $cashier = $this->cashier();
        $product = $this->product($cashier, 10, 1.00);
        $sale = $this->completeCashSale($cashier, $product, 10);

        $this->actingAs($cashier)
            ->post(route('returns.store'), $this->returnPayload($sale, 8))
            ->assertRedirect(route('sales.show', $sale));

        $this->actingAs($cashier)
            ->from(route('returns.sale', ['number' => $sale->sale_number]))
            ->post(route('returns.store'), $this->returnPayload($sale, 3))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(8.0, (float) $sale->items()->first()->fresh()->quantity_returned);
        $this->assertEquals(8.0, (float) $product->fresh()->quantity);
    }

    public function test_cashier_cannot_refund_another_shops_receipt(): void
    {
        $shopA = Shop::factory()->create();
        $shopB = Shop::factory()->create();
        $cashierA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'cashier']);
        $cashierB = User::factory()->create(['shop_id' => $shopB->id, 'role' => 'cashier']);
        $productB = $this->product($cashierB, 10, 1.00);

        $this->actingAs($cashierB);
        $saleB = $this->makeSale($cashierB, $productB, 10);

        $this->actingAs($cashierA)
            ->get(route('returns.sale', ['number' => $saleB->sale_number]))
            ->assertRedirect(route('returns.index'))
            ->assertSessionHas('error');
    }

    public function test_stock_in_no_longer_accepts_customer_return(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $product = $this->product($admin, 0, 1.00);

        $this->actingAs($admin)
            ->from(route('stock.in'))
            ->post(route('stock.in.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
                'reason' => 'Customer Return',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertEquals(0.0, (float) $product->fresh()->quantity);
    }

    private function cashier(): User
    {
        $shop = Shop::factory()->create();

        return User::factory()->create(['shop_id' => $shop->id, 'role' => 'cashier']);
    }

    private function product(User $user, float $quantity = 10, float $price = 1.00): Product
    {
        return Product::factory()->create([
            'shop_id' => $user->shop_id,
            'name' => 'Coke',
            'quantity' => $quantity,
            'selling_price' => $price,
            'unit_price' => $price,
            'cost_price' => round($price * 0.5, 2),
        ]);
    }

    private function completeCashSale(User $cashier, Product $product, float $qty): Sale
    {
        $this->actingAs($cashier)
            ->post(route('pos.store'), [
                'discount' => 0,
                'payment_method' => 'cash',
                'amount_paid' => $qty * (float) $product->selling_price,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => $qty],
                ],
            ])
            ->assertRedirect();

        return Sale::query()->with('items')->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSale(User $user, Product $product, float $qty, array $overrides = []): Sale
    {
        $this->actingAs($user);
        $price = (float) $product->selling_price;
        $total = round($qty * $price, 2);

        $sale = Sale::query()->create([
            'shop_id' => $user->shop_id,
            'sale_number' => Sale::nextNumber(),
            'user_id' => $user->id,
            'sold_at' => $overrides['sold_at'] ?? now(),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'amount_paid' => $total,
            'change_due' => 0,
            'payment_method' => $overrides['payment_method'] ?? 'cash',
            'status' => 'completed',
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => $qty,
            'unit_price' => $price,
            'cost_price' => $product->cost_price,
            'line_total' => $total,
        ]);

        $product->update(['quantity' => round((float) $product->quantity - $qty, 2)]);

        return $sale->fresh('items');
    }

    /**
     * @return array<string, mixed>
     */
    private function returnPayload(Sale $sale, float $qty): array
    {
        $item = $sale->items()->first();

        return [
            'sale_id' => $sale->id,
            'reason' => 'Customer returned items',
            'items' => [
                [
                    'sale_item_id' => $item->id,
                    'quantity' => $qty,
                ],
            ],
        ];
    }
}
