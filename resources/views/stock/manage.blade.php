<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Current quantities, prices, and stock status</x-slot>
    <x-slot name="title">Stock management</x-slot>

    @include('stock._tabs')

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a>
                            </td>
                            <td>
                                @php $pill = $product->isOutOfStock() ? 'stock-pill-out' : ($product->isLowStock() ? 'stock-pill-low' : 'stock-pill-ok'); @endphp
                                <span class="stock-pill {{ $pill }}">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span>
                            </td>
                            <td>{{ money($product->selling_price) }}</td>
                            <td>{{ $product->stockLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No active products.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $products->links() }}</div>
    </div>
</x-app-layout>
