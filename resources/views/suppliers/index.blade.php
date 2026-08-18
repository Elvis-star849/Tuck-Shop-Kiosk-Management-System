<x-app-layout>
    <x-slot name="header">Suppliers</x-slot>
    <x-slot name="subtitle">Who you buy stock from</x-slot>
    <x-slot name="title">Suppliers</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('suppliers.create') }}">New supplier</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Purchases</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td><a href="{{ route('suppliers.show', $supplier) }}" style="color:var(--purple);font-weight:600;">{{ $supplier->name }}</a></td>
                            <td>{{ $supplier->contact_name ?: '—' }}</td>
                            <td>{{ $supplier->phone ?: '—' }}</td>
                            <td>{{ $supplier->purchases_count }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost" href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No suppliers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $suppliers->links() }}</div>
    </div>
</x-app-layout>
