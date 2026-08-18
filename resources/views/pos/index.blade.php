<x-app-layout>
    <x-slot name="header">Point of sale</x-slot>
    <x-slot name="subtitle">Add items, take payment, print a receipt</x-slot>
    <x-slot name="title">POS</x-slot>

    <div
        class="pos-layout"
        x-data="posSale(
            @js($products),
            @js(old('items', [])),
            @js(old('discount', 0)),
            @js(in_array(old('payment_method', 'cash'), ['cash', 'card', 'ecocash'], true) ? old('payment_method', 'cash') : 'cash'),
            @js(old('amount_paid', 0)),
            @js(old('phone', config('paynow.default_phone')))
        )"
    >
        <div class="card card-pad">
            <input class="field" type="search" x-model="search" placeholder="Search name, SKU, or barcode">
            <div class="pos-grid" style="margin-top:14px;">
                <template x-for="product in filtered" :key="product.id">
                    <button
                        type="button"
                        class="pos-tile"
                        :class="{
                            out: Number(product.quantity) <= 0,
                            low: Number(product.quantity) > 0 && Number(product.quantity) <= Number(product.min_stock)
                        }"
                        :disabled="Number(product.quantity) <= 0"
                        @click="add(product)"
                    >
                        <strong x-text="product.name"></strong>
                        <span x-text="money(product.selling_price)"></span>
                        <small x-text="'Stock: ' + formatQty(product.quantity) + ' ' + (product.unit || '')"></small>
                    </button>
                </template>
            </div>
            <p class="empty" x-show="filtered.length === 0" style="padding:24px 0;">No matching products.</p>
        </div>

        <div class="card pos-cart">
            <form method="POST" action="{{ route('pos.store') }}" class="card-pad" x-ref="saleForm" @submit="syncPaid">
                @csrf
                <h2 class="card-title">Current sale</h2>
                <div class="table-wrap" style="margin-top:12px;">
                    <table class="table line-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width:80px;">Qty</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in cart" :key="item.product_id">
                                <tr>
                                    <td>
                                        <div x-text="item.name"></div>
                                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    </td>
                                    <td>
                                        <input class="field" type="number" step="0.01" min="0.01" :max="item.stock" :name="`items[${index}][quantity]`" x-model.number="item.quantity" @change="clamp(item)">
                                    </td>
                                    <td x-text="money(item.quantity * item.price)"></td>
                                    <td><button type="button" class="btn btn-ghost" @click="remove(index)">×</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="empty" x-show="cart.length === 0" style="padding:16px 0;">Tap a product to add it.</p>

                <div class="totals" style="margin-top:12px;">
                    <div class="totals-row"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
                    <div class="totals-row">
                        <span>Discount</span>
                        <input class="field" type="number" step="0.01" min="0" name="discount" x-model.number="discount" style="width:110px;text-align:right;">
                    </div>
                    <div class="totals-row grand"><span>Total</span><span x-text="money(total)"></span></div>
                </div>

                <input type="hidden" name="payment_method" :value="paymentMethod">
                <input type="hidden" name="amount_paid" :value="paymentMethod === 'cash' || paymentMethod === 'card' ? amountPaid : total">

                <label class="field-label" for="payment_method">Payment method</label>
                <select class="field" id="payment_method" x-model="paymentMethod">
                    <option value="cash">Cash</option>
                    <option value="ecocash">EcoCash</option>
                    <option value="card">Card</option>
                </select>

                <div x-show="paymentMethod === 'cash' || paymentMethod === 'card'">
                    <label class="field-label" for="amount_paid" style="margin-top:12px;">Amount paid</label>
                    <input class="field" id="amount_paid" type="number" step="0.01" min="0" x-model.number="amountPaid">

                    <div class="totals-row" style="margin-top:10px;">
                        <span>Change</span>
                        <strong x-text="money(changeDue)"></strong>
                    </div>
                </div>

                <div x-show="paymentMethod === 'ecocash'" x-cloak style="margin-top:12px;">
                    <label class="field-label" for="phone">EcoCash number</label>
                    <input class="field" id="phone" name="phone" x-model="phone" placeholder="{{ config('paynow.default_phone') }}">
                    <p class="muted" style="margin:6px 0 0;">Customer gets a Paynow PIN prompt on this phone.</p>
                </div>

                <button
                    class="btn btn-primary"
                    type="submit"
                    style="width:100%;margin-top:16px;"
                    :class="{ 'btn-ecocash': paymentMethod === 'ecocash' }"
                    :style="paymentMethod === 'ecocash' ? 'background:#00a651' : ''"
                    :disabled="cart.length === 0"
                    @click="if (paymentMethod === 'ecocash') { $event.preventDefault(); payWithEcocash(); }"
                >
                    <span x-text="paymentMethod === 'ecocash' ? 'Pay with EcoCash' : 'Complete sale'"></span>
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
