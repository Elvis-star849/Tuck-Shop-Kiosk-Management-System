<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_shops_register_admin_and_cashier(): void
    {
        $response = $this->post('/register', [
            'shop_name' => 'Mbare Tuck',
            'shop_phone' => '+263 77 000 0000',
            'shop_address' => 'Mbare, Harare',
            'admin_name' => 'Shop Admin',
            'admin_email' => 'admin@mbare.test',
            'admin_password' => 'password',
            'admin_password_confirmation' => 'password',
            'cashier_name' => 'Shop Cashier',
            'cashier_email' => 'cashier@mbare.test',
            'cashier_password' => 'password',
            'cashier_password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseCount('shops', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@mbare.test',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'cashier@mbare.test',
            'role' => 'cashier',
        ]);

        $admin = User::query()->where('email', 'admin@mbare.test')->first();
        $cashier = User::query()->where('email', 'cashier@mbare.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame($admin->shop_id, $cashier->shop_id);
        $this->assertSame('Mbare Tuck', $admin->shop->name);
    }
}
