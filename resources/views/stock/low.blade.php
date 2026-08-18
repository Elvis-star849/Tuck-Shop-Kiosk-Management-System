<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Products at or below the minimum quantity</x-slot>
    <x-slot name="title">Low stock</x-slot>

    @include('stock._tabs')

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Available</th>
                        <th>Min</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                            <td><span class="stock-pill {{ $product->isOutOfStock() ? 'stock-pill-out' : 'stock-pill-low' }}">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</span></td>
                            <td>{{ rtrim(rtrim(number_format($product->min_stock, 2), '0'), '.') }}</td>
                            <td><a class="btn btn-outline" href="{{ route('stock.in') }}">Stock in</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Stock levels look healthy.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $products->links() }}</div>
    </div>
</x-app-layout>
