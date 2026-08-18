<x-app-layout>
    <x-slot name="header">{{ $sale->sale_number }}</x-slot>
    <x-slot name="subtitle">{{ $sale->sold_at->format('d M Y H:i') }} · {{ $sale->user?->name }}</x-slot>
    <x-slot name="title">Sale</x-slot>
    <x-slot name="actions">
        @if ($sale->isPendingPayment())
            <a class="btn btn-primary" href="{{ route('sales.ecocash.create', $sale) }}" style="background:#00a651;">Pay with EcoCash</a>
            <form method="POST" action="{{ route('sales.paynow.start', $sale) }}">
                @csrf
                <button class="btn btn-primary" type="submit" style="background:#ef4444;">Pay with Paynow</button>
            </form>
        @else
            <a class="btn btn-primary" href="{{ route('sales.pdf', $sale) }}">Download receipt</a>
            <a class="btn btn-ghost" href="{{ route('sales.receipt', $sale) }}">Print receipt</a>
        @endif
        <a class="btn btn-ghost" href="{{ route('sales.index') }}">{{ auth()->user()->isAdmin() ? 'All sales' : 'My sales' }}</a>
    </x-slot>

    <div class="chart-grid">
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                <td>{{ money($item->unit_price) }}</td>
                                <td>{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-pad">
            <div class="totals-row"><span>Subtotal</span><span>{{ money($sale->subtotal) }}</span></div>
            <div class="totals-row"><span>Discount</span><span>{{ money($sale->discount) }}</span></div>
            <div class="totals-row grand"><span>Total</span><span>{{ money($sale->total) }}</span></div>
            <div class="totals-row"><span>Paid</span><span>{{ money($sale->amount_paid) }}</span></div>
            <div class="totals-row"><span>Change</span><span>{{ money($sale->change_due) }}</span></div>
            @if (auth()->user()->isAdmin())
                <div class="totals-row"><span>Profit</span><span>{{ money($sale->profit()) }}</span></div>
            @endif
            <p class="muted" style="margin-top:12px;">{{ $sale->isPendingPayment() ? 'Payment method' : 'Paid by' }} {{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }}</p>
            <p style="margin-top:8px;"><x-status-badge :status="$sale->status" /></p>
        </div>
    </div>

    @if ($sale->isPendingPayment())
        <div class="card card-pad" style="margin-top:18px;">
            <h2 class="card-title">Collect payment</h2>
            <p class="muted">This sale is held until Paynow or EcoCash confirms. Stock is already reserved.</p>
            <div class="actions" style="margin-top:12px;">
                <a class="btn btn-primary" href="{{ route('sales.ecocash.create', $sale) }}" style="background:#00a651;">Pay with EcoCash</a>
                <form method="POST" action="{{ route('sales.paynow.start', $sale) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit" style="background:#ef4444;">Pay with Paynow</button>
                </form>
                <form method="POST" action="{{ route('sales.void-pending', $sale) }}" onsubmit="return confirm('Void this unpaid sale and restore stock?')">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Void unpaid sale</button>
                </form>
            </div>
        </div>
    @endif

    @if ($sale->gatewayTransactions->isNotEmpty())
        <div class="card" style="margin-top:18px;">
            <div class="card-pad"><h2 class="card-title">Gateway payments</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->gatewayTransactions->sortByDesc('id') as $transaction)
                            <tr>
                                <td>{{ \App\Models\Payment::METHODS[$transaction->method] ?? $transaction->method }}</td>
                                <td>{{ $transaction->phone ?: '—' }}</td>
                                <td>{{ money($transaction->amount) }}</td>
                                <td><x-status-badge :status="$transaction->status" /></td>
                                <td>{{ $transaction->reference }}</td>
                                <td>
                                    @if ($transaction->isPending())
                                        <a class="btn btn-ghost" href="{{ route('payments.ecocash.show', $transaction) }}">Open</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($sale->isCompleted())
        <form method="POST" action="{{ route('sales.cancel-request', $sale) }}" class="card card-pad" style="margin-top:18px;max-width:640px;">
            @csrf
            <h2 class="card-title">Request cancellation</h2>
            <p class="muted">Cashiers cannot delete a completed sale. An admin must approve.</p>
            <label class="field-label" for="cancel_reason">Reason</label>
            <textarea class="field" id="cancel_reason" name="cancel_reason" rows="2" required>{{ old('cancel_reason') }}</textarea>
            <button class="btn btn-outline" type="submit" style="margin-top:12px;">Request cancellation</button>
        </form>
    @endif

    @if (auth()->user()->isAdmin() && $sale->isCancelRequested())
        <div class="card card-pad" style="margin-top:18px;">
            <h2 class="card-title">Cancellation request</h2>
            <p>{{ $sale->cancel_reason }}</p>
            <p class="muted">Requested {{ optional($sale->cancel_requested_at)->format('d M Y H:i') }}</p>
            <div class="actions" style="margin-top:12px;">
                <form method="POST" action="{{ route('sales.cancel-approve', $sale) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve & restore stock</button>
                </form>
                <form method="POST" action="{{ route('sales.cancel-reject', $sale) }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Reject</button>
                </form>
            </div>
        </div>
    @endif

    @if (auth()->user()->isAdmin() && $sale->isCompleted())
        <form method="POST" action="{{ route('sales.cancel-approve', $sale) }}" class="card card-pad" style="margin-top:12px;max-width:640px;" onsubmit="return confirm('Cancel this sale and restore stock?')">
            @csrf
            <button class="btn btn-ghost" type="submit">Cancel sale now (admin)</button>
        </form>
    @endif
</x-app-layout>
