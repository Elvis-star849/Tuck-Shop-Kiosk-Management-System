<x-app-layout>
    <x-slot name="header">Return items</x-slot>
    <x-slot name="subtitle">Receipt {{ $sale->sale_number }} · {{ $sale->sold_at->format('d M Y H:i') }}</x-slot>
    <x-slot name="title">Return</x-slot>
    <x-slot name="actions">
        <a class="btn btn-ghost" href="{{ route('sales.show', $sale) }}">View sale</a>
        <a class="btn btn-ghost" href="{{ route('returns.index') }}">Look up another</a>
    </x-slot>

    <div class="card card-pad" style="margin-bottom:18px;max-width:720px;">
        <div class="totals-row"><span>Cashier</span><span>{{ $sale->user?->name }}</span></div>
        <div class="totals-row"><span>Paid by</span><span>{{ \App\Models\Payment::METHODS[$sale->payment_method] ?? $sale->payment_method }}</span></div>
        <div class="totals-row"><span>Sale total</span><span>{{ money($sale->total) }}</span></div>
        <div class="totals-row"><span>Already refunded</span><span>{{ money($sale->approvedRefundTotal()) }}</span></div>
        <div class="totals-row grand"><span>Still refundable</span><span>{{ money($sale->remainingRefundableTotal()) }}</span></div>
        <p class="muted" style="margin-top:12px;">
            Refund amount is calculated from this receipt. You cannot type a different amount.
            @if ($sale->payment_method === 'cash' && $sale->sold_at->isToday())
                Same-day cash returns complete at the till unless they are over the shop limit.
            @else
                This return will wait for an admin to approve before stock or money moves.
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('returns.store') }}" class="card card-pad">
        @csrf
        <input type="hidden" name="sale_id" value="{{ $sale->id }}">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Sold</th>
                        <th>Already returned</th>
                        <th>Return qty</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $index => $item)
                        @php $available = $item->returnableQuantity(); @endphp
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($item->quantity_returned, 2), '0'), '.') }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                <input
                                    class="field"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="{{ $available }}"
                                    name="items[{{ $index }}][quantity]"
                                    value="{{ old('items.'.$index.'.quantity', $available > 0 ? '' : '0') }}"
                                    {{ $available <= 0 ? 'disabled' : '' }}
                                    style="max-width:120px;"
                                >
                                <div class="muted">Max {{ rtrim(rtrim(number_format($available, 2), '0'), '.') }}</div>
                            </td>
                            <td>{{ money($item->unit_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;max-width:640px;">
            <label class="field-label" for="reason">Reason</label>
            <textarea class="field" id="reason" name="reason" rows="2" required>{{ old('reason') }}</textarea>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit">Submit return</button>
            <a class="btn btn-ghost" href="{{ route('returns.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
