<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:160'],
            'shop_phone' => ['nullable', 'string', 'max:40'],
            'shop_address' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cashier_name' => ['required', 'string', 'max:255'],
            'cashier_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'different:admin_email', 'unique:users,email'],
            'cashier_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $admin = DB::transaction(function () use ($data) {
            $shop = Shop::query()->create([
                'name' => $data['shop_name'],
                'phone' => $data['shop_phone'] ?? null,
                'address' => $data['shop_address'] ?? null,
                'status' => 'active',
            ]);

            $admin = User::query()->create([
                'shop_id' => $shop->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            User::query()->create([
                'shop_id' => $shop->id,
                'name' => $data['cashier_name'],
                'email' => $data['cashier_email'],
                'password' => Hash::make($data['cashier_password']),
                'role' => 'cashier',
                'email_verified_at' => now(),
            ]);

            return $admin;
        });

        event(new Registered($admin));
        Auth::login($admin);

        Setting::put('company.name', $data['shop_name']);
        Setting::put('company.phone', $data['shop_phone'] ?? '');
        Setting::put('company.address', $data['shop_address'] ?? '');
        AuditLog::record('shop.registered', $admin->name.' registered shop '.$data['shop_name'], $admin->shop);

        return redirect(route('dashboard', absolute: false));
    }
}
