<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Set the real counted quantity. The difference is recorded with who, when, and why.</x-slot>
    <x-slot name="title">Stock adjustment</x-slot>

    @include('stock._tabs')

    <form
        method="POST"
        action="{{ route('stock.adjust.store') }}"
        class="card card-pad"
        style="max-width:720px;"
        x-data="{
            products: @js($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'quantity' => (float) $p->quantity, 'unit' => $p->unit])->values()),
            productId: '{{ old('product_id') }}',
            actual: '{{ old('actual_quantity') }}',
            get selected() { return this.products.find((p) => String(p.id) === String(this.productId)) },
            get system() { return this.selected ? Number(this.selected.quantity) : 0 },
            get diff() { return this.actual === '' ? 0 : Number(this.actual) - this.system }
        }"
    >
        @csrf
        <div class="form-grid">
            <div class="full">
                <label class="field-label" for="product_id">Product</label>
                <select class="field" id="product_id" name="product_id" x-model="productId" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">System quantity</label>
                <input class="field" type="text" :value="selected ? system + ' ' + selected.unit : '—'" disabled>
            </div>
            <div>
                <label class="field-label" for="actual_quantity">Actual quantity</label>
                <input class="field" id="actual_quantity" type="number" step="0.01" min="0" name="actual_quantity" x-model="actual" required>
            </div>
            <div class="full">
                <p class="muted" x-show="selected && actual !== ''">
                    Difference: <strong x-text="(diff > 0 ? '+' : '') + diff"></strong>
                </p>
            </div>
            <div class="full">
                <label class="field-label" for="reason">Reason</label>
                <select class="field" id="reason" name="reason" required>
                    <option value="Stock Count Difference" @selected(old('reason') === 'Stock Count Difference')>Stock Count Difference</option>
                    <option value="Damaged" @selected(old('reason') === 'Damaged')>Damaged</option>
                    <option value="Expired" @selected(old('reason') === 'Expired')>Expired</option>
                    <option value="Found stock" @selected(old('reason') === 'Found stock')>Found stock</option>
                    <option value="Other" @selected(old('reason') === 'Other')>Other</option>
                </select>
            </div>
            <div class="full">
                <label class="field-label" for="notes">Comment</label>
                <textarea class="field" id="notes" name="notes" rows="3" required>{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Confirm adjustment</button>
            <a class="btn btn-ghost" href="{{ route('stock.manage') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
