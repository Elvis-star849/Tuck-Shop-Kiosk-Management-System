<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_product_stores_an_activity_log(): void
    {
        $shop = Shop::factory()->create();
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        $this->actingAs($admin);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Cooking Oil']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.created',
            'auditable_id' => $product->id,
            'user_id' => $admin->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_admins_can_view_the_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }

    public function test_cashiers_cannot_view_the_activity_log(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
