<x-app-layout>
    <x-slot name="header">{{ $supplier->name }}</x-slot>
    <x-slot name="subtitle">{{ $supplier->contact_name ?: 'Supplier' }}</x-slot>
    <x-slot name="title">{{ $supplier->name }}</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('purchases.create') }}">New purchase</a>
        <a class="btn btn-ghost" href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
    </x-slot>

    <div class="card card-pad" style="margin-bottom:18px;">
        <p>{{ $supplier->phone ?: 'No phone' }} · {{ $supplier->email ?: 'No email' }}</p>
        <p class="muted">{{ $supplier->address }}</p>
    </div>

    <div class="card">
        <div class="card-pad"><h2 class="card-title">Purchases</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Purchase</th>
                        <th>Date</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplier->purchases as $purchase)
                        <tr>
                            <td><a href="{{ route('purchases.show', $purchase) }}" style="color:var(--purple);font-weight:600;">{{ $purchase->purchase_number }}</a></td>
                            <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td>{{ money($purchase->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">No purchases from this supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
