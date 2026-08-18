<x-app-layout>
    <x-slot name="header">Record payment</x-slot>
    <x-slot name="subtitle">Apply cash, transfer, card, or mobile money to an invoice</x-slot>
    <x-slot name="title">Record payment</x-slot>

    <form method="POST" action="{{ route('payments.store') }}" class="card card-pad">
        @csrf
        <div class="form-grid">
            <div class="full">
                <label class="field-label" for="invoice_id">Invoice</label>
                <select class="field" id="invoice_id" name="invoice_id" required>
                    <option value="">Select invoice</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->id }}" @selected((string) old('invoice_id', $selected) === (string) $invoice->id)>
                            {{ $invoice->invoice_number }} — {{ $invoice->customer?->displayName() }} (balance {{ money($invoice->balance()) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="amount">Amount</label>
                <input class="field" id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
            </div>
            <div>
                <label class="field-label" for="payment_date">Payment date</label>
                <input class="field" id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="payment_method">Method</label>
                <select class="field" id="payment_method" name="payment_method" required>
                    @foreach (\App\Models\Payment::METHODS as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="payment_reference">Reference</label>
                <input class="field" id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}">
            </div>
            <div class="full">
                <label class="field-label" for="notes">Notes</label>
                <textarea class="field" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>
        <p class="muted" style="margin-top:10px;">To collect EcoCash from the customer's phone, open the invoice and choose Pay with EcoCash.</p>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save payment</button>
            <a class="btn btn-ghost" href="{{ route('payments.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
