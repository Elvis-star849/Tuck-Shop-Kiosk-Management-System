<x-app-layout>
    <x-slot name="header">Pay with EcoCash</x-slot>
    <x-slot name="subtitle">
        {{ $sale?->sale_number ?? $invoice->invoice_number }}
        · balance {{ money($sale?->balance() ?? $invoice->balance()) }}
    </x-slot>
    <x-slot name="title">EcoCash payment</x-slot>

    <div class="card card-pad ecocash-card" style="margin-bottom:18px;">
        <div style="display:flex;gap:16px;align-items:center;">
            <div class="icon-circle" style="background:#00a651;">
                <span class="material-symbols-outlined">smartphone</span>
            </div>
            <div>
                <h2 class="card-title">EcoCash via Paynow</h2>
                <p class="muted" style="margin:4px 0 0;">The customer will get a PIN prompt on their EcoCash phone.</p>
            </div>
        </div>
    </div>

    @if ($simulating)
        <div class="flash flash-success" style="background:#e8f8ef;color:#0f7a3d;">
            Demo mode is on. Default EcoCash number: <strong>{{ config('paynow.default_phone') }}</strong> (paid). Other test numbers: <strong>0771111111</strong> (paid), <strong>0772222222</strong> (delayed), <strong>0773333333</strong> (cancelled), <strong>0774444444</strong> (failed).
        </div>
    @endif

    <form method="POST" action="{{ $sale ? route('sales.ecocash.store', $sale) : route('payments.ecocash.store', $invoice) }}" class="card card-pad">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label" for="amount">Amount</label>
                <input class="field" id="amount" type="number" step="0.01" min="0.01" max="{{ $sale?->balance() ?? $invoice->balance() }}" name="amount" value="{{ old('amount', $sale?->balance() ?? $invoice->balance()) }}" required>
            </div>
            <div>
                <label class="field-label" for="phone">EcoCash number</label>
                <input class="field" id="phone" name="phone" placeholder="{{ config('paynow.default_phone') }}" value="{{ old('phone', $invoice?->customer?->phone ?: config('paynow.default_phone')) }}" required>
            </div>
        </div>
        @if ($invoice)
            <p class="muted" style="margin-top:10px;">Bill to: {{ $invoice->customer?->displayName() }}</p>
        @endif
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit" style="background:#00a651;">Send EcoCash PIN prompt</button>
            <a class="btn btn-ghost" href="{{ $sale ? route('sales.show', $sale) : route('invoices.show', $invoice) }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
