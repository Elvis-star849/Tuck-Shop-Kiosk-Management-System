<x-app-layout>
    <x-slot name="header">Products</x-slot>
    <x-slot name="subtitle">Stock, prices, and catalog</x-slot>
    <x-slot name="title">Products</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, SKU, barcode">
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
            <a class="btn btn-primary" href="{{ route('products.create') }}">
                <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add product
            </a>
        @endif
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        @if (auth()->user()->isAdmin())
                            <th>Cost</th>
                        @endif
                        <th>Selling</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                            <td>{{ $product->category?->name ?: '—' }}</td>
                            @if (auth()->user()->isAdmin())
                                <td>{{ money($product->cost_price) }}</td>
                            @endif
                            <td>{{ money($product->selling_price) }}</td>
                            <td>
                                @if ($product->isOutOfStock())
                                    <span class="stock-pill stock-pill-out">Out</span>
                                @elseif ($product->isLowStock())
                                    <span class="stock-pill stock-pill-low">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span>
                                @else
                                    <span class="stock-pill stock-pill-ok">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span>
                                @endif
                            </td>
                            <td>{{ \App\Models\Product::STATUSES[$product->status] ?? $product->status }}</td>
                            <td>
                                <div class="actions">
                                    @if (auth()->user()->isAdmin())
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
                        <tr><td colspan="8" class="empty">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $products->links() }}</div>
    </div>
</x-app-layout>
