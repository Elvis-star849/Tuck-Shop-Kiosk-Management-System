<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Every in, out, who, and when</x-slot>
    <x-slot name="title">Stock history</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <select class="field" name="product_id" onchange="this.form.submit()">
                <option value="">All products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            <select class="field" name="type" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach (\App\Models\StockMovement::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </x-slot>

    @include('stock._tabs')

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $movement->product?->name }}</td>
                            <td><span class="badge badge-{{ $movement->type }}">{{ \App\Models\StockMovement::TYPES[$movement->type] ?? $movement->type }}</span></td>
                            <td class="{{ $movement->quantity >= 0 ? 'qty-in' : 'qty-out' }}">
                                {{ $movement->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($movement->quantity, 2), '0'), '.') }}
                            </td>
                            <td>{{ rtrim(rtrim(number_format($movement->quantity_before, 2), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($movement->quantity_after, 2), '0'), '.') }}</td>
                            <td>{{ $movement->reason ?: '—' }}@if ($movement->notes)<div class="muted">{{ $movement->notes }}</div>@endif</td>
                            <td>{{ $movement->user?->name ?: 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $movements->links() }}</div>
    </div>
</x-app-layout>
