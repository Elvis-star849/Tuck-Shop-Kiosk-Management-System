<x-app-layout>
    <x-slot name="header">Products</x-slot>
    <x-slot name="subtitle">Stock, prices, and catalog</x-slot>
    <x-slot name="title">Products</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search name">
            <select class="field" name="stock" onchange="this.form.submit()">
                <option value="">All stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <select class="field" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (\App\Models\Product::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Search</button>
        </form>
        @if (auth()->user()->isAdmin())
            <a class="btn btn-primary" href="{{ route('purchases.create') }}">
                <span class="material-symbols-outlined" style="font-size:18px;">add</span> New purchase
            </a>
        @endif
    </x-slot>

    @php
        $isAdmin = auth()->user()->isAdmin();
        $emptyCols = $isAdmin ? 10 : 7;
    @endphp

    @if ($isAdmin)
        <div class="stat-grid">
            <div class="card stat-card">
                <div>
                    <div class="stat-value">{{ money($sellingTotal) }}</div>
                    <div class="stat-label">Total selling price</div>
                    <div class="stat-change">If all stock is sold</div>
                </div>
                <div class="icon-circle bg-purple">
                    <span class="material-symbols-outlined">sell</span>
                </div>
            </div>
            <div class="card stat-card yellow">
                <div>
                    <div class="stat-value">{{ money($purchaseTotal) }}</div>
                    <div class="stat-label">Total purchase price</div>
                    <div class="stat-change">Cost of stock on hand</div>
                </div>
                <div class="icon-circle bg-yellow">
                    <span class="material-symbols-outlined">shopping_bag</span>
                </div>
            </div>
            <div class="card stat-card teal">
                <div>
                    <div class="stat-value">{{ money_profit($expectedProfit) }}</div>
                    <div class="stat-label">{{ $expectedProfit < 0 ? 'Expected loss' : 'Expected profit' }}</div>
                    <div class="stat-change">After selling all stock</div>
                </div>
                <div class="icon-circle bg-teal">
                    <span class="material-symbols-outlined">trending_up</span>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        @if ($isAdmin)
                            <th>Cost</th>
                        @endif
                        <th>Selling</th>
                        @if ($isAdmin)
                            <th>Purchase total</th>
                        @endif
                        <th>Selling total</th>
                        @if ($isAdmin)
                            <th>Expected profit</th>
                        @endif
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                            <td>{{ $product->category?->name ?: '—' }}</td>
                            <td>
                                @if ($product->isOutOfStock())
                                    <span class="stock-pill stock-pill-out">Out</span>
                                @elseif ($product->isLowStock())
                                    <span class="stock-pill stock-pill-low">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span>
                                @else
                                    <span class="stock-pill stock-pill-ok">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span>
                                @endif
                            </td>
                            @if ($isAdmin)
                                <td>{{ money($product->cost_price) }}</td>
                            @endif
                            <td>{{ money($product->selling_price) }}</td>
                            @if ($isAdmin)
                                <td>{{ money($product->stockPurchaseTotal()) }}</td>
                            @endif
                            <td>{{ money($product->stockSellingTotal()) }}</td>
                            @if ($isAdmin)
                                <td>{{ money_profit($product->expectedStockProfit()) }}</td>
                            @endif
                            <td>{{ \App\Models\Product::STATUSES[$product->status] ?? $product->status }}</td>
                            <td>
                                <div class="actions">
                                    @if ($isAdmin)
                                        <a class="btn btn-ghost" href="{{ route('products.edit', $product) }}">Edit</a>
                                        @if ($product->status !== 'discontinued')
                                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Discontinue this product? History is kept.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-ghost" type="submit">Discontinue</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $emptyCols }}" class="empty">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($isAdmin)
            <div class="card-pad">
                <div class="totals-row"><span>Total purchase price</span><span>{{ money($purchaseTotal) }}</span></div>
                <div class="totals-row"><span>Total selling price</span><span>{{ money($sellingTotal) }}</span></div>
                <div class="totals-row grand"><span>{{ $expectedProfit < 0 ? 'Expected loss' : 'Expected profit' }}</span><span>{{ money_profit($expectedProfit) }}</span></div>
                {{ $products->links() }}
            </div>
        @else
            <div class="card-pad">{{ $products->links() }}</div>
        @endif
    </div>
</x-app-layout>
