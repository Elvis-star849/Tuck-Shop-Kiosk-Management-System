<x-app-layout>
    <x-slot name="header">Payments</x-slot>
        <x-slot name="subtitle">Money collected from POS and invoices</x-slot>
    <x-slot name="title">Payments</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search reference or invoice">
            <button class="btn btn-ghost" type="submit">Search</button>
        </form>
        <a class="btn btn-primary" href="{{ route('payments.create') }}">Record payment</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td>
                                @if ($payment->sale_id)
                                    <a href="{{ route('sales.show', $payment->sale_id) }}" style="color:var(--purple);font-weight:600;">{{ $payment->sale?->sale_number }}</a>
                                @elseif ($payment->invoice_id)
                                    <a href="{{ route('invoices.show', $payment->invoice_id) }}" style="color:var(--purple);font-weight:600;">{{ $payment->invoice?->invoice_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $payment->invoice?->customer?->displayName() ?: 'Walk-in' }}</td>
                            <td>{{ \App\Models\Payment::METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td>{{ $payment->payment_reference ?: '—' }}</td>
                            <td>{{ money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $payments->links() }}</div>
    </div>
</x-app-layout>
