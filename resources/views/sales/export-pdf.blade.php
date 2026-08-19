<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2f3343; font-size: 12px; }
        .top { border-bottom: 3px solid #5b4ee6; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #5b4ee6; letter-spacing: 2px; font-weight: bold; font-size: 16px; text-transform: uppercase; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        h2 { margin: 18px 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #5b4ee6; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #ddd; padding: 7px 6px; }
        td { padding: 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .right { text-align: right; }
        .sale-head td { border-bottom: 0; padding-bottom: 2px; }
        .sale-num { font-weight: bold; color: #5b4ee6; }
        .items { color: #4b5563; font-size: 11px; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { border: 0; padding: 4px 6px; }
        .grand td { font-weight: bold; font-size: 15px; border-top: 1px solid #ddd; }
        .empty { text-align: center; color: #6b7280; padding: 24px 0; }
    </style>
</head>
<body>
    <div class="top">
        <div class="brand">{{ config('company.name') }}</div>
        <div class="muted">{{ config('company.address') }} · {{ config('company.phone') }}</div>
        <h1>Sales report</h1>
        <div>{{ $cashier?->name ?? 'All cashiers' }}</div>
        <div class="muted">{{ $period }} · {{ $sales->count() }} {{ \Illuminate\Support\Str::plural('sale', $sales->count()) }}</div>
    </div>

    @if ($soldItems->isNotEmpty())
        <h2>Items sold</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="right">Qty</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($soldItems as $row)
                    <tr>
                        <td>{{ $row->description }}</td>
                        <td class="right">{{ rtrim(rtrim(number_format($row->qty, 2), '0'), '.') }}</td>
                        <td class="right">{{ money($row->revenue) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Receipts</h2>
    @if ($sales->isEmpty())
        <div class="empty">No sales in this period.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Sale</th>
                    <th>Date</th>
                    @if (! $cashier)
                        <th>Cashier</th>
                    @endif
                    <th>Items</th>
                    <th>Method</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sales as $sale)
                    <tr>
                        <td class="sale-num">{{ $sale->sale_number }}</td>
                        <td>{{ $sale->sold_at->format('d M Y H:i') }}</td>
                        @if (! $cashier)
                            <td>{{ $sale->user?->name }}</td>
                        @endif
                        <td class="items">
                            @foreach ($sale->items as $item)
                                {{ $item->description }} × {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                                @if (! $loop->last)<br>@endif
                            @endforeach
                        </td>
                        <td>{{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }}</td>
                        <td class="right">{{ money($sale->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="totals">
        <tr class="grand">
            <td>Grand total</td>
            <td class="right">{{ money($total) }}</td>
        </tr>
    </table>
</body>
</html>
