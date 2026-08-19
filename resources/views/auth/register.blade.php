<x-guest-layout>
    <x-slot name="title">Register your shop · {{ config('app.name') }}</x-slot>
    <h1>Register your tuck-shop</h1>
    <p class="muted" style="margin-bottom:22px;">Create the shop account, then set up the admin and cashier who will use it.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <h2 class="card-title" style="margin:0 0 12px;">Shop</h2>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="shop_name">Shop name</label>
            <input id="shop_name" class="field" type="text" name="shop_name" value="{{ old('shop_name') }}" required autofocus>
            <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="shop_phone">Phone</label>
            <input id="shop_phone" class="field" type="text" name="shop_phone" value="{{ old('shop_phone') }}">
            <x-input-error :messages="$errors->get('shop_phone')" class="mt-2" />
        </div>
        <div style="margin-bottom:18px;">
            <label class="field-label" for="shop_address">Address</label>
            <input id="shop_address" class="field" type="text" name="shop_address" value="{{ old('shop_address') }}">
            <x-input-error :messages="$errors->get('shop_address')" class="mt-2" />
        </div>

        <h2 class="card-title" style="margin:8px 0 12px;">Admin</h2>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="admin_name">Name</label>
            <input id="admin_name" class="field" type="text" name="admin_name" value="{{ old('admin_name') }}" required>
            <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="admin_email">Email</label>
            <input id="admin_email" class="field" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
            <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="admin_password">Password</label>
            <input id="admin_password" class="field" type="password" name="admin_password" required>
            <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
        </div>
        <div style="margin-bottom:18px;">
            <label class="field-label" for="admin_password_confirmation">Confirm password</label>
            <input id="admin_password_confirmation" class="field" type="password" name="admin_password_confirmation" required>
        </div>

        <h2 class="card-title" style="margin:8px 0 12px;">Cashier</h2>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="cashier_name">Name</label>
            <input id="cashier_name" class="field" type="text" name="cashier_name" value="{{ old('cashier_name') }}" required>
            <x-input-error :messages="$errors->get('cashier_name')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="cashier_email">Email</label>
            <input id="cashier_email" class="field" type="email" name="cashier_email" value="{{ old('cashier_email') }}" required>
            <x-input-error :messages="$errors->get('cashier_email')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="cashier_password">Password</label>
            <input id="cashier_password" class="field" type="password" name="cashier_password" required>
            <x-input-error :messages="$errors->get('cashier_password')" class="mt-2" />
        </div>
        <div style="margin-bottom:18px;">
            <label class="field-label" for="cashier_password_confirmation">Confirm password</label>
            <input id="cashier_password_confirmation" class="field" type="password" name="cashier_password_confirmation" required>
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;">Create shop</button>
        <p style="margin-top:14px;font-size:13px;text-align:center;">
            <a href="{{ route('login') }}" style="color:var(--purple);">Already registered?</a>
        </p>
    </form>
</x-guest-layout>
