<x-app-layout>
    <x-slot name="header">Customers</x-slot>
    <x-slot name="subtitle">People and companies you bill</x-slot>
    <x-slot name="title">Customers</x-slot>
    <x-slot name="actions">
        <form method="GET" action="{{ route('customers.index') }}" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search customers">
            <button class="btn btn-ghost" type="submit">Search</button>
        </form>
        <a class="btn btn-primary" href="{{ route('customers.create') }}">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span> New customer
        </a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Invoices</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><a href="{{ route('customers.show', $customer) }}" style="color:var(--purple);font-weight:600;">{{ $customer->name }}</a></td>
                            <td>{{ $customer->company_name ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->invoices_count }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost" href="{{ route('customers.edit', $customer) }}">Edit</a>
                                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $customers->links() }}</div>
    </div>
</x-app-layout>
