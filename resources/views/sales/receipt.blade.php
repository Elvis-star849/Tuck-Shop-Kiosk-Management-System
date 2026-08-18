<x-app-layout>
    <x-slot name="header">Receipt</x-slot>
    <x-slot name="subtitle">{{ $sale->sale_number }}</x-slot>
    <x-slot name="title">Receipt</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('sales.pdf', $sale) }}">Download PDF</a>
        <button class="btn btn-ghost" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-ghost" href="{{ route('pos.index') }}">New sale</a>
    </x-slot>

    @push('styles')
        <style>
            @media print {
                .material-topbar, .sidenav, .page-head, .flash { display: none !important; }
                .app-shell { display: block; }
                .app-main { padding: 0; }
                body { background: #fff; }
            }
        </style>
    @endpush

    <div class="receipt-paper">
        <div class="receipt-brand">{{ strtoupper(config('company.name')) }}</div>
        <div class="muted">{{ config('company.address') }}</div>
        <div class="muted">{{ config('company.phone') }}</div>
        <hr>
        <div>Sale: {{ $sale->sale_number }}</div>
        <div>Date: {{ $sale->sold_at->format('d M Y H:i') }}</div>
        <div>Cashier: {{ $sale->user?->name }}</div>
        <hr>
        <table>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->description }} x {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td style="text-align:right;">{{ money($item->line_total) }}</td>
                </tr>
            @endforeach
        </table>
        <hr>
        <div class="totals-row"><span>Subtotal</span><span>{{ money($sale->subtotal) }}</span></div>
        @if ($sale->discount > 0)
            <div class="totals-row"><span>Discount</span><span>{{ money($sale->discount) }}</span></div>
        @endif
        <div class="totals-row grand"><span>Total</span><span>{{ money($sale->total) }}</span></div>
        <div class="totals-row"><span>Paid ({{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }})</span><span>{{ money($sale->amount_paid) }}</span></div>
        <div class="totals-row"><span>Change</span><span>{{ money($sale->change_due) }}</span></div>
        <hr>
        <p style="text-align:center;margin:12px 0 0;">{{ config('company.receipt_footer', 'Thank you') }}</p>
    </div>
</x-app-layout>
