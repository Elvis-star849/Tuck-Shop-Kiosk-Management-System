<x-app-layout>
    <x-slot name="header">Stock management</x-slot>
    <x-slot name="subtitle">Damaged, expired, lost, or adjustment</x-slot>
    <x-slot name="title">Stock out</x-slot>

    @include('stock._tabs')

    <form method="POST" action="{{ route('stock.out.store') }}" class="card card-pad" style="max-width:720px;">
        @csrf
        <div class="form-grid">
            <div class="full">
                <label class="field-label" for="product_id">Product</label>
                <select class="field" id="product_id" name="product_id" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                            {{ $product->name }} — available {{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="quantity">Quantity out</label>
                <input class="field" id="quantity" type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" required>
            </div>
            <div>
                <label class="field-label" for="reason">Reason</label>
                <select class="field" id="reason" name="reason" required>
                    @foreach (\App\Models\StockMovement::OUT_REASONS as $key => $label)
                        <option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="full">
                <label class="field-label" for="notes">Notes</label>
                <textarea class="field" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Remove stock</button>
            <a class="btn btn-ghost" href="{{ route('stock.manage') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
