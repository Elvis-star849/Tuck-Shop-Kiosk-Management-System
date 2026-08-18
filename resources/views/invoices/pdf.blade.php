<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2f3343; font-size: 13px; }
        .top { border-bottom: 3px solid #5b4ee6; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { color: #5b4ee6; letter-spacing: 2px; font-weight: bold; font-size: 16px; text-transform: uppercase; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #ddd; padding: 8px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .totals { width: 280px; margin-left: auto; margin-top: 12px; }
        .totals td { border: 0; }
        .grand td { font-weight: bold; font-size: 16px; border-top: 1px solid #ddd; }
        .status { margin-top: 24px; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="top">
        <div class="brand">{{ config('company.name') }}</div>
        <div class="muted">{{ config('company.address') }} · {{ config('company.email') }} · {{ config('company.phone') }}</div>
    </div>

    <table>
        <tr>
            <td>
                <h1>INVOICE: {{ $invoice->invoice_number }}</h1>
                <div>Date: {{ $invoice->invoice_date->format('d F Y') }}</div>
                <div>Due date: {{ $invoice->due_date->format('d F Y') }}</div>
            </td>
            <td style="text-align:right;">
                <strong>BILL TO:</strong><br>
                {{ $invoice->customer?->displayName() }}<br>
                {{ $invoice->customer?->address }}<br>
                {{ $invoice->customer?->email }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td>{{ money($item->unit_price) }}</td>
                    <td>{{ money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td style="text-align:right;">{{ money($invoice->subtotal) }}</td></tr>
        <tr><td>VAT</td><td style="text-align:right;">{{ money($invoice->tax_amount) }}</td></tr>
        <tr><td>Discount</td><td style="text-align:right;">- {{ money($invoice->discount) }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td style="text-align:right;">{{ money($invoice->total) }}</td></tr>
        <tr><td>Paid</td><td style="text-align:right;">{{ money($invoice->amount_paid) }}</td></tr>
        <tr><td>Balance</td><td style="text-align:right;">{{ money($invoice->balance()) }}</td></tr>
    </table>

    <div class="status">Payment status: {{ strtoupper(invoice_status_label($invoice->status)) }}</div>
    @if ($invoice->notes)
        <p class="muted">Notes: {{ $invoice->notes }}</p>
    @endif
</body>
</html>
