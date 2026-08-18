<x-app-layout>
    <x-slot name="header">{{ $product->exists ? 'Edit product' : 'New product' }}</x-slot>
    <x-slot name="subtitle">SKU, prices, and stock settings. Quantity changes go through Stock in/out.</x-slot>
    <x-slot name="title">{{ $product->exists ? 'Edit product' : 'New product' }}</x-slot>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="card card-pad">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div>
                <label class="field-label" for="sku">SKU</label>
                <input class="field" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
            </div>
            <div>
                <label class="field-label" for="barcode">Barcode</label>
                <input class="field" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}">
            </div>
            <div class="full">
                <label class="field-label" for="name">Name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $product->name) }}" required>
            </div>
            <div>
                <label class="field-label" for="category_id">Category</label>
                <select class="field" id="category_id" name="category_id">
                    <option value="">None</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="supplier_id">Supplier</label>
                <select class="field" id="supplier_id" name="supplier_id">
                    <option value="">None</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="full">
                <label class="field-label" for="description">Description</label>
                <textarea class="field" id="description" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div>
                <label class="field-label" for="cost_price">Cost price</label>
                <input class="field" id="cost_price" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? '0.00') }}" required>
            </div>
            <div>
                <label class="field-label" for="selling_price">Selling price</label>
                <input class="field" id="selling_price" type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? $product->unit_price ?? '0.00') }}" required>
            </div>
            <div>
                <label class="field-label" for="tax_rate">Tax rate (%)</label>
                <input class="field" id="tax_rate" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate ?? config('company.default_tax_rate')) }}" required>
            </div>
            <div>
                <label class="field-label" for="min_stock">Minimum stock</label>
                <input class="field" id="min_stock" type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 5) }}" required>
            </div>
            <div>
                <label class="field-label" for="unit">Unit</label>
                <input class="field" id="unit" name="unit" value="{{ old('unit', $product->unit ?? 'items') }}" required>
            </div>
            <div>
                <label class="field-label" for="expiry_date">Expiry date</label>
                <input class="field" id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date', optional($product->expiry_date)->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="field-label" for="status">Status</label>
                <select class="field" id="status" name="status" required>
                    @foreach (\App\Models\Product::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $product->status ?? 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($product->exists)
            <p class="muted" style="margin-top:12px;">Current quantity: {{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}. Use Stock in / Stock out to change it.</p>
        @endif

        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save product</button>
            <a class="btn btn-ghost" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
