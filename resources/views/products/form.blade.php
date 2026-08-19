<x-app-layout>
    <x-slot name="header">Edit product</x-slot>
    <x-slot name="subtitle">Change name, category, or selling price. Quantity and cost are updated on Purchases.</x-slot>
    <x-slot name="title">Edit product</x-slot>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="card card-pad" style="max-width:720px;">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div class="full">
                <label class="field-label" for="name">Product name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="e.g. Coca Cola 500ml" required>
            </div>
            <div
                x-data="{
                    open: false,
                    highlight: 0,
                    categoryId: @js(old('category_id', $product->category_id)),
                    categoryName: @js(old('category_name', $product->category?->name ?? '')),
                    categories: @js($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values()),
                    get query() { return this.categoryName.trim().toLowerCase(); },
                    get choices() {
                        if (! this.query) return this.categories.slice(0, 8);
                        return this.categories.filter((row) => row.name.toLowerCase().includes(this.query)).slice(0, 8);
                    },
                    get isNew() {
                        return this.query !== '' && ! this.categories.some((row) => row.name.toLowerCase() === this.query);
                    },
                    syncId() {
                        const match = this.categories.find((row) => row.name.toLowerCase() === this.query);
                        this.categoryId = match ? match.id : '';
                    },
                    pick(row) {
                        this.categoryId = row.id;
                        this.categoryName = row.name;
                        this.open = false;
                    },
                    keepNew() {
                        this.categoryId = '';
                        this.open = false;
                    },
                    move(step) {
                        const count = this.choices.length + (this.isNew ? 1 : 0);
                        if (! count) return;
                        this.highlight = (this.highlight + step + count) % count;
                    },
                    chooseHighlighted() {
                        if (this.highlight < this.choices.length) {
                            this.pick(this.choices[this.highlight]);
                            return;
                        }
                        if (this.isNew) this.keepNew();
                    },
                }"
                @click.outside="open = false"
            >
                <label class="field-label" for="category_name">Category</label>
                <div class="combo">
                    <input type="hidden" name="category_id" :value="categoryId">
                    <input
                        class="field"
                        id="category_name"
                        name="category_name"
                        x-model="categoryName"
                        autocomplete="off"
                        placeholder="Type a category, e.g. Drinks"
                        required
                        @focus="open = true"
                        @input="syncId(); open = true; highlight = 0"
                        @keydown.arrow-down.prevent="move(1)"
                        @keydown.arrow-up.prevent="move(-1)"
                        @keydown.enter.prevent="chooseHighlighted()"
                        @keydown.escape="open = false"
                    >
                    <div class="combo-list" x-show="open && (choices.length || isNew)" x-cloak>
                        <template x-for="(choice, i) in choices" :key="'c-'+choice.id">
                            <button
                                type="button"
                                class="combo-option"
                                :class="{ active: i === highlight }"
                                @mousedown.prevent="pick(choice)"
                            >
                                <span x-text="choice.name"></span>
                            </button>
                        </template>
                        <button
                            type="button"
                            class="combo-option combo-create"
                            x-show="isNew"
                            @mousedown.prevent="keepNew()"
                        >
                            Create “<span x-text="categoryName.trim()"></span>”
                        </button>
                    </div>
                </div>
                <p class="combo-hint" x-show="isNew">New category — will be saved with this product.</p>
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
            <div>
                <label class="field-label" for="selling_price">Selling price</label>
                <input class="field" id="selling_price" type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? $product->unit_price ?? '0.00') }}" required>
            </div>
            <div>
                <label class="field-label" for="expiry_date">Expiry date</label>
                <input class="field" id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date', optional($product->expiry_date)->format('Y-m-d')) }}">
            </div>
            @if ($product->exists)
                <div>
                    <label class="field-label" for="status">Status</label>
                    <select class="field" id="status" name="status" required>
                        @foreach (\App\Models\Product::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $product->status ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($product->exists)
            <p class="muted" style="margin-top:12px;">
                In stock: {{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }} {{ $product->unit }}
                · Cost {{ money($product->cost_price) }}.
                To add stock or change cost, record a
                <a href="{{ route('purchases.create') }}" style="color:var(--purple);font-weight:600;">new purchase</a>.
            </p>
        @endif

        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save product</button>
            <a class="btn btn-ghost" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
