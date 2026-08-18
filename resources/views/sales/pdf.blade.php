<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $sale->sale_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2f3343; font-size: 12px; }
        .wrap { width: 320px; margin: 0 auto; }
        .brand { text-align: center; letter-spacing: 2px; font-weight: bold; font-size: 14px; text-transform: uppercase; color: #5b4ee6; }
        .muted { color: #6b7280; text-align: center; font-size: 11px; }
        .line { border: 0; border-top: 1px dashed #ccc; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; }
        .right { text-align: right; }
        .row { width: 100%; }
        .grand td { font-weight: bold; font-size: 14px; padding-top: 6px; }
        .thanks { text-align: center; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">{{ config('company.name') }}</div>
        <div class="muted">{{ config('company.address') }}</div>
        <div class="muted">{{ config('company.phone') }}</div>
        <hr class="line">
        <div>Sale: {{ $sale->sale_number }}</div>
        <div>Date: {{ $sale->sold_at->format('d M Y H:i') }}</div>
        <div>Cashier: {{ $sale->user?->name }}</div>
        <hr class="line">
        <table>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->description }} x {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ money($item->line_total) }}</td>
                </tr>
            @endforeach
        </table>
        <hr class="line">
        <table>
            <tr><td>Subtotal</td><td class="right">{{ money($sale->subtotal) }}</td></tr>
            @if ($sale->discount > 0)
                <tr><td>Discount</td><td class="right">{{ money($sale->discount) }}</td></tr>
            @endif
            <tr class="grand"><td>Total</td><td class="right">{{ money($sale->total) }}</td></tr>
            <tr><td>Paid ({{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }})</td><td class="right">{{ money($sale->amount_paid) }}</td></tr>
            <tr><td>Change</td><td class="right">{{ money($sale->change_due) }}</td></tr>
        </table>
        <hr class="line">
        <p class="thanks">{{ config('company.receipt_footer', 'Thank you') }}</p>
    </div>
</body>
</html>
