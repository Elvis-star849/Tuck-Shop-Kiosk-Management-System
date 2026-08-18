<x-app-layout>
    <x-slot name="header">{{ auth()->user()->isAdmin() ? 'All sales' : 'My sales' }}</x-slot>
    <x-slot name="subtitle">{{ auth()->user()->isAdmin() ? 'Every cashier’s completed and cancelled sales' : 'Your till receipts' }}</x-slot>
    <x-slot name="title">Sales</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('pos.index') }}">New sale</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Method</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td><a href="{{ route('sales.show', $sale) }}" style="color:var(--purple);font-weight:600;">{{ $sale->sale_number }}</a></td>
                            <td>{{ $sale->sold_at->format('d M Y H:i') }}</td>
                            <td>{{ $sale->user?->name }}</td>
                            <td>{{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }}</td>
                            <td>{{ money($sale->total) }}</td>
                            <td><x-status-badge :status="$sale->status" /></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost" href="{{ route('sales.receipt', $sale) }}">View</a>
                                    <a class="btn btn-ghost" href="{{ route('sales.pdf', $sale) }}">Download</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $sales->links() }}</div>
    </div>
</x-app-layout>
