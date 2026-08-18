<x-app-layout>
    <x-slot name="header">Users</x-slot>
    <x-slot name="subtitle">Admin controls the business. Cashier sells at the till.</x-slot>
    <x-slot name="title">Users</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('users.create') }}">New user</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td><a class="btn btn-ghost" href="{{ route('users.edit', $user) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No users.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $users->links() }}</div>
    </div>
</x-app-layout>
