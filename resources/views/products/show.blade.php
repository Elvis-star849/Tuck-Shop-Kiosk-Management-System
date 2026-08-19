<x-app-layout>
    <x-slot name="header">{{ $product->name }}</x-slot>
    <x-slot name="subtitle">{{ $product->category?->name ?: 'Uncategorised' }}</x-slot>
    <x-slot name="title">{{ $product->name }}</x-slot>
    <x-slot name="actions">
        @if (auth()->user()->isAdmin())
            <a class="btn btn-primary" href="{{ route('products.edit', $product) }}">Edit</a>
            <a class="btn btn-ghost" href="{{ route('purchases.create') }}">New purchase</a>
            <a class="btn btn-ghost" href="{{ route('stock.adjust') }}">Adjust stock</a>
            <a class="btn btn-ghost" href="{{ route('stock.history', ['product_id' => $product->id]) }}">Full history</a>
        @endif
    </x-slot>

    <div class="stat-grid">
        <div class="card stat-card">
            <div>
                <div class="stat-value">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }}</div>
                <div class="stat-label">Available ({{ $product->unit }})</div>
            </div>
        </div>
        <div class="card stat-card yellow">
            <div>
                <div class="stat-value">{{ money($product->selling_price) }}</div>
                <div class="stat-label">Selling price</div>
            </div>
        </div>
        @if (auth()->user()->isAdmin())
        <div class="card stat-card teal">
            <div>
                <div class="stat-value">{{ money($product->cost_price) }}</div>
                <div class="stat-label">Cost price</div>
            </div>
        </div>
        <div class="card stat-card magenta">
            <div>
                <div class="stat-value">{{ money_profit($product->profitPerUnit()) }}</div>
                <div class="stat-label">{{ $product->profitPerUnit() < 0 ? 'Loss / unit' : 'Profit / unit' }}</div>
            </div>
        </div>
        @endif
    </div>

    @if (auth()->user()->isAdmin())
    <div class="chart-grid">
        <div class="card">
            <div class="card-pad"><h2 class="card-title">Stock history</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->stockMovements->take(15) as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                                <td>{{ \App\Models\StockMovement::TYPES[$movement->type] ?? $movement->type }}</td>
                                <td class="{{ $movement->quantity >= 0 ? 'qty-in' : 'qty-out' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($movement->quantity, 2), '0'), '.') }}</td>
                                <td>{{ rtrim(rtrim(number_format($movement->quantity_before, 2), '0'), '.') }}</td>
                                <td>{{ rtrim(rtrim(number_format($movement->quantity_after, 2), '0'), '.') }}</td>
                                <td>{{ $movement->user?->name ?: 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No movements yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-pad"><h2 class="card-title">Price & setting changes</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Field</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>Who</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($audits as $audit)
                            <tr>
                                <td>{{ $audit->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $audit->field }}</td>
                                <td>{{ $audit->old_value }}</td>
                                <td>{{ $audit->new_value }}</td>
                                <td>{{ $audit->user?->name ?: 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">No audited changes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>
