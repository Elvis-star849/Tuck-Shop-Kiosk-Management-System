<x-guest-layout>
    <x-slot name="title">Register · {{ config('app.name') }}</x-slot>
    <h1>Create account</h1>
    <p class="muted" style="margin-bottom:22px;">Set up access to the invoice generation system.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div style="margin-bottom:14px;">
            <label class="field-label" for="name">Name</label>
            <input id="name" class="field" type="text" name="name" value="{{ old('name') }}" required autofocus>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="email">Email</label>
            <input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div style="margin-bottom:14px;">
            <label class="field-label" for="password">Password</label>
            <input id="password" class="field" type="password" name="password" required>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div style="margin-bottom:18px;">
            <label class="field-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" class="field" type="password" name="password_confirmation" required>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;">Register</button>
        <p style="margin-top:14px;font-size:13px;text-align:center;">
            <a href="{{ route('login') }}" style="color:var(--purple);">Already registered?</a>
        </p>
    </form>
</x-guest-layout>
