@php
    $invoice = $invoice ?? null;
    $defaultItems = $invoice
        ? $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
        ])->values()->all()
        : [];
    $existingItems = old('items', $defaultItems);
    $selectedCustomer = old('customer_id', $invoice?->customer_id ?? request('customer_id'));
@endphp

<div
    class="card"
    x-data="invoiceForm(@js($products), @js($existingItems), @js(old('discount', $invoice?->discount ?? 0)))"
>
    <form method="POST" action="{{ $invoice ? route('invoices.update', $invoice) : route('invoices.store') }}" class="card-pad">
        @csrf
        @if ($invoice)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div>
                <label class="field-label" for="customer_id">Customer</label>
                <select class="field" id="customer_id" name="customer_id" required>
                    <option value="">Select customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomer === (string) $customer->id)>
                            {{ $customer->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="invoice_date">Invoice date</label>
                <input class="field" id="invoice_date" type="date" name="invoice_date" value="{{ old('invoice_date', optional($invoice?->invoice_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="due_date">Due date</label>
                <input class="field" id="due_date" type="date" name="due_date" value="{{ old('due_date', optional($invoice?->due_date)->format('Y-m-d') ?? now()->addDays(7)->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="discount">Discount</label>
                <input class="field" id="discount" type="number" step="0.01" min="0" name="discount" x-model.number="discount" value="{{ old('discount', $invoice?->discount ?? 0) }}">
            </div>
            <div class="full">
                <label class="field-label" for="notes">Notes</label>
                <textarea class="field" id="notes" name="notes" rows="2">{{ old('notes', $invoice?->notes ?? '') }}</textarea>
            </div>
        </div>

        <h3 style="margin:22px 0 10px;font-size:16px;font-weight:500;">Select products</h3>
        <p class="muted" style="margin:0 0 12px;">Click a saved product to add it to this invoice. Quantity can be changed on the line below.</p>

        @if ($products->isEmpty())
            <div class="flash flash-error">
                No products yet. <a href="{{ route('purchases.create') }}" style="color:inherit;font-weight:700;">Record a purchase</a> first, then come back to create the invoice.
            </div>
        @else
            <div class="product-picker">
                @foreach ($products as $product)
                    <button
                        type="button"
                        class="product-chip"
                        @click="addExistingProduct({{ $product['id'] }})"
                    >
                        <strong>{{ $product['name'] }}</strong>
                        <span>{{ money($product['unit_price']) }} · {{ rtrim(rtrim(number_format($product['quantity'], 2), '0'), '.') }} in stock</span>
                    </button>
                @endforeach
            </div>
        @endif

        <h3 style="margin:22px 0 10px;font-size:16px;font-weight:500;">Invoice items</h3>
        <div class="table-wrap">
            <table class="table line-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Description</th>
                        <th style="width:90px;">Qty</th>
                        <th style="width:120px;">Price</th>
                        <th style="width:90px;">Tax %</th>
                        <th style="width:120px;">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr>
                            <td>
                                <select class="field" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="fillProduct(index)" required>
                                    <option value="">Select product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product['id'] }}">{{ $product['name'] }} — {{ money($product['unit_price']) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="field" :name="`items[${index}][description]`" x-model="item.description" required></td>
                            <td><input class="field" type="number" step="0.01" min="0.01" :max="stockFor(item.product_id)" :name="`items[${index}][quantity]`" x-model.number="item.quantity" @change="clampQty(index)" required></td>
                            <td><input class="field" type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" required></td>
                            <td><input class="field" type="number" step="0.01" min="0" :name="`items[${index}][tax_rate]`" x-model.number="item.tax_rate" required></td>
                            <td x-text="money(lineTotal(item))"></td>
                            <td>
                                <button type="button" class="btn btn-ghost" @click="removeItem(index)">Remove</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <p class="empty" x-show="items.length === 0" style="padding:24px 8px;">No products selected yet. Choose a product above.</p>

        <button type="button" class="btn btn-outline" style="margin-top:12px;" @click="addItem()" x-show="products.length">+ Add item</button>

        <div class="totals">
            <div class="totals-row"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
            <div class="totals-row"><span>Tax</span><span x-text="money(tax)"></span></div>
            <div class="totals-row"><span>Discount</span><span x-text="'- ' + money(discount)"></span></div>
            <div class="totals-row grand"><span>Total</span><span x-text="money(total)"></span></div>
        </div>

        <div class="actions" style="margin-top:20px;">
            <button class="btn btn-ghost" type="submit" name="status_action" value="draft">Save draft</button>
            <button class="btn btn-primary" type="submit" name="status_action" value="generate">Generate invoice</button>
            <a class="btn btn-ghost" href="{{ $invoice ? route('invoices.show', $invoice) : route('invoices.index') }}">Cancel</a>
        </div>
    </form>
</div>
