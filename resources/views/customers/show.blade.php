<x-app-layout>
    <x-slot name="header">{{ $customer->displayName() }}</x-slot>
    <x-slot name="subtitle">{{ $customer->email }} {{ $customer->phone ? '· '.$customer->phone : '' }}</x-slot>
    <x-slot name="title">{{ $customer->displayName() }}</x-slot>
    <x-slot name="actions">
        <a class="btn btn-ghost" href="{{ route('customers.edit', $customer) }}">Edit</a>
        <a class="btn btn-primary" href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}">Create invoice</a>
    </x-slot>

    <div class="card card-pad" style="margin-bottom:18px;">
        <p style="margin:0;color:#5b6172;">{{ $customer->address ?: 'No address on file.' }}</p>
    </div>

    <div class="card">
        <div class="card-pad"><h2 class="card-title">Invoices</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customer->invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show', $invoice) }}" style="color:var(--purple);font-weight:600;">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                            <td>{{ money($invoice->total) }}</td>
                            <td>{{ money($invoice->balance()) }}</td>
                            <td><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No invoices for this customer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
