<x-app-layout>
    <x-slot name="header">{{ $user->exists ? 'Edit user' : 'New user' }}</x-slot>
    <x-slot name="subtitle">Admin sees everything in this shop. Cashier uses POS and stock views.</x-slot>
    <x-slot name="title">{{ $user->exists ? 'Edit user' : 'New user' }}</x-slot>

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card card-pad" style="max-width:720px;">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <div>
                <label class="field-label" for="name">Name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input class="field" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div>
                <label class="field-label" for="role">Role</label>
                <select class="field" id="role" name="role" required>
                    <option value="cashier" @selected(old('role', $user->role ?? 'cashier') === 'cashier')>Cashier</option>
                    <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')>Admin</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="password">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label>
                <input class="field" id="password" type="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8">
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save user</button>
            <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
