<x-app-layout>
    <x-slot name="header">Invoices</x-slot>
    <x-slot name="subtitle">Create, send, collect, and download PDFs</x-slot>
    <x-slot name="title">Invoices</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search invoice or customer">
            <select class="field" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\Invoice::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ invoice_status_label($status) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
        <a class="btn btn-primary" href="{{ route('invoices.create') }}">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span> New invoice
        </a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Due</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show', $invoice) }}" style="color:var(--purple);font-weight:600;">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->customer?->displayName() }}</td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                            <td>{{ $invoice->due_date->format('d M Y') }}</td>
                            <td>{{ money($invoice->total) }}</td>
                            <td>{{ money($invoice->balance()) }}</td>
                            <td><x-status-badge :status="$invoice->status" /></td>
                            <td>
                                <a class="btn btn-ghost" href="{{ route('invoices.pdf', $invoice) }}">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
