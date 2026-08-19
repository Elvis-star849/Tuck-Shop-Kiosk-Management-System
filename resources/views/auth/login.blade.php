<x-guest-layout>
    <x-slot name="title">Sign in · {{ config('app.name') }}</x-slot>

    <h1>Welcome back</h1>
    <p class="muted" style="margin-bottom:22px;">Sign in to sell at the till or manage the shop.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div style="margin-bottom:14px;">
            <label class="field-label" for="email">Email</label>
            <input id="email" class="field" type="email" name="email" value="{{ old('email', 'admin@chindeka.test') }}" required autofocus>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div style="margin-bottom:14px;">
            <label class="field-label" for="password">Password</label>
            <input id="password" class="field" type="password" name="password" required>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#5b6172;margin-bottom:18px;">
            <input type="checkbox" name="remember"> Remember me
        </label>

        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;">Log in</button>

        <div class="muted" style="margin-top:16px;font-size:13px;text-align:center;line-height:1.6;">
            <div>Admin: <strong>admin@chindeka.test</strong> / <strong>password</strong></div>
            <div>Cashier: <strong>cashier@chindeka.test</strong> / <strong>password</strong></div>
        </div>

        <p style="margin-top:10px;font-size:13px;text-align:center;">
            <a href="{{ route('register') }}" style="color:var(--purple);">Register your shop</a>
        </p>
    </form>
</x-guest-layout>
