<x-app-layout>
    <x-slot name="header">{{ $purchase->purchase_number }}</x-slot>
    <x-slot name="subtitle">{{ $purchase->supplier?->name }} · {{ $purchase->purchase_date->format('d M Y') }}</x-slot>
    <x-slot name="title">Purchase</x-slot>

    <div class="card">
        <div class="card-pad muted">
            Recorded by {{ $purchase->user?->name ?: 'System' }}
            @if ($purchase->reference) · Ref {{ $purchase->reference }} @endif
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Line</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                            <td>{{ money($item->cost_price) }}</td>
                            <td>{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad">
            <div class="totals-row grand"><span>Total</span><span>{{ money($purchase->total) }}</span></div>
            @if ($purchase->notes)
                <p class="muted" style="margin-top:12px;">{{ $purchase->notes }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
