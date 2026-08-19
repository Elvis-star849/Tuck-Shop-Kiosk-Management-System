<x-app-layout>
    <x-slot name="header">
        {{ $cashier?->name ? $cashier->name.'’s sales' : (auth()->user()->isAdmin() ? 'All sales' : 'My sales') }}
    </x-slot>
    <x-slot name="subtitle">
        @if (! empty($from) && ! empty($to))
            {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}
            –
            {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
        @elseif (! empty($date))
            Sales on {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
        @else
            {{ auth()->user()->isAdmin() ? 'Every cashier’s completed and cancelled sales' : 'Your till receipts' }}
        @endif
    </x-slot>
    <x-slot name="title">Sales</x-slot>
    <x-slot name="actions">
        @if ($cashier)
            <a class="btn btn-ghost" href="{{ route('reports.index', ['tab' => 'cashiers']) }}">Back to cashiers</a>
            <a class="btn btn-ghost" href="{{ route('sales.index') }}">All sales</a>
        @elseif (! empty($date) || ! empty($from))
            <a class="btn btn-ghost" href="{{ route('sales.index') }}">All sales</a>
        @endif
        <a class="btn btn-ghost" href="{{ route('sales.export', request()->only(['cashier_id', 'from', 'to', 'date', 'status'])) }}">Download sales</a>
        <a class="btn btn-primary" href="{{ route('pos.index') }}">New sale</a>
    </x-slot>

    @if ($cashier && $soldItems->isNotEmpty())
        <div class="card" style="margin-bottom:18px;">
            <div class="card-pad">
                <h2 class="card-title">Items sold by {{ $cashier->name }}</h2>
                <div class="card-kicker">What they sold in this period</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($soldItems as $row)
                            <tr>
                                <td>{{ $row->description }}</td>
                                <td>{{ rtrim(rtrim(number_format($row->qty, 2), '0'), '.') }}</td>
                                <td>{{ money($row->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-pad">
            <h2 class="card-title">{{ $cashier ? 'Receipts' : 'Sales' }}</h2>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Items</th>
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
                            <td>
                                {{ $sale->items->map(fn ($item) => rtrim(rtrim(number_format($item->quantity, 2), '0'), '.').'× '.$item->description)->implode(', ') ?: '—' }}
                            </td>
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
                        <tr><td colspan="8" class="empty">{{ ! empty($date) || ! empty($from) ? 'No sales in this period.' : 'No sales yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $sales->links() }}</div>
    </div>
</x-app-layout>
