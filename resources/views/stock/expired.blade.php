<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Expired items and products that expire soon</x-slot>
    <x-slot name="title">Expiry</x-slot>

    @include('stock._tabs')

    <div class="chart-grid">
        <div class="card">
            <div class="card-pad">
                <h2 class="card-title">Expired</h2>
                <div class="card-kicker">Expiry date has passed</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Expiry</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expired as $product)
                            <tr>
                                <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                                <td>{{ $product->expiry_date->format('d M Y') }}</td>
                                <td>{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">No expired products.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-pad">
                <h2 class="card-title">Expiring soon</h2>
                <div class="card-kicker">Within the next 14 days</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Expiry</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expiringSoon as $product)
                            <tr>
                                <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                                <td>{{ $product->expiry_date->format('d M Y') }}</td>
                                <td>{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">Nothing expiring in the next 14 days.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
