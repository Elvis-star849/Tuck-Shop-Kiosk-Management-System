<x-app-layout>
    <x-slot name="header">Purchases</x-slot>
    <x-slot name="subtitle">Supplier stock received</x-slot>
    <x-slot name="title">Purchases</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('purchases.create') }}">New purchase</a>
    </x-slot>

    <div class="stat-grid">
        <div class="card stat-card">
            <div>
                <div class="stat-value">{{ money($grandTotal) }}</div>
                <div class="stat-label">Grand total</div>
                <div class="stat-change">{{ $purchases->total() }} {{ $purchases->total() === 1 ? 'purchase' : 'purchases' }}</div>
            </div>
            <div class="icon-circle bg-purple">
                <span class="material-symbols-outlined">local_shipping</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Purchase</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td><a href="{{ route('purchases.show', $purchase) }}" style="color:var(--purple);font-weight:600;">{{ $purchase->purchase_number }}</a></td>
                            <td>{{ $purchase->supplier?->name }}</td>
                            <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td>{{ money($purchase->total) }}</td>
                            <td>
                                <a class="btn btn-ghost" href="{{ route('purchases.edit', $purchase) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No purchases yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">
            <div class="totals-row grand"><span>Grand total</span><span>{{ money($grandTotal) }}</span></div>
            {{ $purchases->links() }}
        </div>
    </div>
</x-app-layout>
