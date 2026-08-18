<x-app-layout>
    <x-slot name="header">{{ $transaction->isPaynowWeb() ? 'Waiting for Paynow' : 'Waiting for EcoCash' }}</x-slot>
    <x-slot name="subtitle">{{ $transaction->payableLabel() }} · {{ money($transaction->amount) }}@if ($transaction->phone) to {{ $transaction->phone }}@endif</x-slot>
    <x-slot name="title">{{ $transaction->isPaynowWeb() ? 'Paynow payment' : 'EcoCash payment' }}</x-slot>

    <div
        class="card card-pad"
        x-data="{
            url: '{{ route('payments.ecocash.poll', $transaction) }}',
            status: '{{ $transaction->status }}',
            label: '{{ $transaction->status === 'pending' ? ($transaction->isPaynowWeb() ? 'Waiting for Paynow…' : 'Waiting for EcoCash PIN…') : invoice_status_label($transaction->status) }}',
            async poll() {
                const response = await fetch(this.url, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                this.status = data.status;
                this.label = data.message;
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            },
            init() {
                if (this.status === 'pending') {
                    setInterval(() => this.poll(), 4000);
                    this.poll();
                }
            }
        }"
    >
        <div style="display:flex;gap:16px;align-items:flex-start;">
            <div class="icon-circle" style="background: {{ $transaction->isPaynowWeb() ? '#ef4444' : '#00a651' }};">
                <span class="material-symbols-outlined">{{ $transaction->isPaynowWeb() ? 'account_balance' : 'phone_iphone' }}</span>
            </div>
            <div style="flex:1;">
                <h2 class="card-title">{{ $transaction->isPaynowWeb() ? 'Complete payment on Paynow' : "Approve on the customer's phone" }}</h2>
                <p style="margin:8px 0 0;">{{ $transaction->instructions }}</p>
                <p class="muted" style="margin:10px 0 0;">Reference: {{ $transaction->reference }}</p>
            </div>
        </div>

        <div style="margin-top:22px;padding:16px;border-radius:10px;background:{{ $transaction->isPaynowWeb() ? '#fef2f2' : '#f3fbf6' }};">
            <strong x-text="label"></strong>
            <p class="muted" style="margin:6px 0 0;" x-show="status === 'pending'">This page checks Paynow every few seconds. Keep it open until payment is confirmed.</p>
        </div>

        <div class="actions" style="margin-top:18px;">
            <a class="btn btn-ghost" href="{{ $transaction->payableShowRoute() }}">Back</a>
            @if ($transaction->redirectUrl() && $transaction->isPending())
                <a class="btn btn-primary" href="{{ $transaction->redirectUrl() }}" style="background:#ef4444;">Continue to Paynow</a>
            @endif
            @if ($simulating && $transaction->isPending())
                <form method="POST" action="{{ route('payments.ecocash.simulate', $transaction) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit" style="background:{{ $transaction->isPaynowWeb() ? '#ef4444' : '#00a651' }};">Confirm demo payment</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
