<x-app-layout>
    <x-slot name="header">{{ $invoice->invoice_number }}</x-slot>
    <x-slot name="subtitle">{{ $invoice->customer?->displayName() }} · due {{ $invoice->due_date->format('d M Y') }}</x-slot>
    <x-slot name="title">{{ $invoice->invoice_number }}</x-slot>
    <x-slot name="actions">
        <a class="btn btn-ghost" href="{{ route('invoices.print', $invoice) }}" target="_blank">Print</a>
        <a class="btn btn-ghost" href="{{ route('invoices.pdf', $invoice) }}">Download PDF</a>
        @if ($invoice->isEditable())
            <a class="btn btn-ghost" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
        @endif
        @if ($invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Mark as sent</button>
            </form>
        @endif
        @if ($invoice->balance() > 0 && ! in_array($invoice->status, ['draft', 'cancelled']))
            <a class="btn btn-primary" href="{{ route('payments.ecocash.create', $invoice) }}" style="background:#00a651;">Pay with EcoCash</a>
            @if (auth()->user()->isAdmin())
                <a class="btn btn-ghost" href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}">Record payment</a>
            @endif
        @endif
    </x-slot>

    <div class="card" style="margin-bottom:18px;">
        <div class="invoice-hero">
            <div>
                <div class="company-mark">{{ config('company.name') }}</div>
                <p class="muted" style="margin:8px 0 0;">{{ config('company.address') }}<br>{{ config('company.email') }} · {{ config('company.phone') }}</p>
            </div>
            <div style="text-align:right;">
                <x-status-badge :status="$invoice->status" />
                <p style="margin:10px 0 0;font-size:13px;color:#5b6172;">
                    Date: {{ $invoice->invoice_date->format('d F Y') }}<br>
                    Due: {{ $invoice->due_date->format('d F Y') }}
                </p>
            </div>
        </div>
        <div class="card-pad">
            <strong>Bill to</strong>
            <p style="margin:6px 0 0;">
                {{ $invoice->customer?->displayName() }}<br>
                {{ $invoice->customer?->address }}<br>
                {{ $invoice->customer?->email }}
            </p>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                            <td>{{ money($item->unit_price) }}</td>
                            <td>{{ number_format($item->tax_rate, 1) }}%</td>
                            <td>{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad">
            <div class="totals">
                <div class="totals-row"><span>Subtotal</span><span>{{ money($invoice->subtotal) }}</span></div>
                <div class="totals-row"><span>VAT / tax</span><span>{{ money($invoice->tax_amount) }}</span></div>
                <div class="totals-row"><span>Discount</span><span>- {{ money($invoice->discount) }}</span></div>
                <div class="totals-row grand"><span>Total</span><span>{{ money($invoice->total) }}</span></div>
                <div class="totals-row"><span>Amount paid</span><span>{{ money($invoice->amount_paid) }}</span></div>
                <div class="totals-row"><span>Balance</span><span>{{ money($invoice->balance()) }}</span></div>
            </div>
            @if ($invoice->notes)
                <p class="muted" style="margin-top:16px;">Notes: {{ $invoice->notes }}</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-pad" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="card-title">Payments</h2>
            @if (! in_array($invoice->status, ['cancelled', 'paid']))
                <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?')">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Cancel invoice</button>
                </form>
            @endif
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td>{{ \App\Models\Payment::METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td>{{ $payment->payment_reference ?: '—' }}</td>
                            <td>{{ money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($invoice->gatewayTransactions->where('status', 'pending')->isNotEmpty())
        <div class="card" style="margin-top:18px;">
            <div class="card-pad"><h2 class="card-title">Pending EcoCash</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->gatewayTransactions->where('status', 'pending') as $transaction)
                            <tr>
                                <td>{{ $transaction->phone }}</td>
                                <td>{{ money($transaction->amount) }}</td>
                                <td>{{ $transaction->reference }}</td>
                                <td><a class="btn btn-ghost" href="{{ route('payments.ecocash.show', $transaction) }}">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
