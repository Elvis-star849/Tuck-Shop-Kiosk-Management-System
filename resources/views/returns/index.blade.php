<x-app-layout>
    <x-slot name="header">Returns / refunds</x-slot>
    <x-slot name="subtitle">Look up a receipt number. No receipt, no refund.</x-slot>
    <x-slot name="title">Returns</x-slot>
    <x-slot name="actions">
        <a class="btn btn-ghost" href="{{ route('sales.index', ['status' => 'cancel_requested']) }}">Pending sale voids</a>
    </x-slot>

    <form method="POST" action="{{ route('returns.lookup') }}" class="card card-pad" style="max-width:640px;">
        @csrf
        <h2 class="card-title">Find receipt</h2>
        <p class="muted">Type the number printed on the receipt, for example SALE-00016.</p>
        <label class="field-label" for="number">Receipt number</label>
        <input class="field" id="number" name="number" value="{{ old('number', $number) }}" placeholder="SALE-00016" required autocomplete="off">
        <div class="actions" style="margin-top:12px;">
            <button class="btn btn-primary" type="submit">Look up receipt</button>
        </div>
    </form>

    @if ($pending->isNotEmpty())
        <div class="card" style="margin-top:18px;">
            <div class="card-pad">
                <h2 class="card-title">Waiting for approval</h2>
                <div class="card-kicker">Stock and money have not moved yet</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Return</th>
                            <th>Receipt</th>
                            <th>Requested by</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pending as $return)
                            <tr>
                                <td>{{ $return->return_number }}</td>
                                <td><a href="{{ route('sales.show', $return->sale_id) }}" style="color:var(--purple);font-weight:600;">{{ $return->sale?->sale_number }}</a></td>
                                <td>{{ $return->requestedBy?->name }}</td>
                                <td>{{ money($return->refund_amount) }}</td>
                                <td>{{ \App\Models\Payment::METHODS[$return->refund_method] ?? $return->refund_method }}</td>
                                <td>
                                    @if (auth()->user()->isAdmin())
                                        <div class="actions">
                                            <form method="POST" action="{{ route('returns.approve', $return) }}">
                                                @csrf
                                                <button class="btn btn-primary" type="submit">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('returns.reject', $return) }}">
                                                @csrf
                                                <button class="btn btn-ghost" type="submit">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <x-status-badge :status="$return->status" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($recent->isNotEmpty())
        <div class="card" style="margin-top:18px;">
            <div class="card-pad"><h2 class="card-title">Recent returns</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Return</th>
                            <th>Receipt</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $return)
                            <tr>
                                <td>{{ $return->return_number }}</td>
                                <td>{{ $return->sale?->sale_number }}</td>
                                <td>{{ money($return->refund_amount) }}</td>
                                <td><x-status-badge :status="$return->status" /></td>
                                <td>{{ $return->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
