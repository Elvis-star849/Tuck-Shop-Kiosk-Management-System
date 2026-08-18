        <div class="form-grid">
            <div>
                <label class="field-label" for="supplier_name">Supplier</label>
                <div class="combo" @click.outside="supplierOpen = false">
                    <input type="hidden" name="supplier_id" :value="supplierId">
                    <input
                        class="field"
                        id="supplier_name"
                        name="supplier_name"
                        x-model="supplierName"
                        autocomplete="off"
                        placeholder="Type or select a supplier"
                        required
                        @focus="supplierOpen = true"
                        @input="onSupplierInput()"
                        @keydown.arrow-down.prevent="moveSupplier(1)"
                        @keydown.arrow-up.prevent="moveSupplier(-1)"
                        @keydown.enter.prevent="pickHighlightedSupplier()"
                        @keydown.escape="supplierOpen = false"
                    >
                    <div class="combo-list" x-show="supplierOpen && (supplierChoices.length || isNewSupplier)" x-cloak>
                        <template x-for="(choice, i) in supplierChoices" :key="'s-'+choice.id">
                            <button
                                type="button"
                                class="combo-option"
                                :class="{ active: i === supplierIndex }"
                                @mousedown.prevent="pickSupplier(choice)"
                            >
                                <span x-text="choice.name"></span>
                            </button>
                        </template>
                        <button
                            type="button"
                            class="combo-option combo-create"
                            x-show="isNewSupplier"
                            @mousedown.prevent="keepNewSupplier()"
                        >
                            Create “<span x-text="supplierName.trim()"></span>”
                        </button>
                    </div>
                </div>
                <p class="combo-hint" x-show="isNewSupplier">New supplier — will be saved with this purchase.</p>
            </div>
            <div>
                <label class="field-label" for="purchase_date">Date</label>
                <input class="field" id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', isset($purchase) ? $purchase->purchase_date->toDateString() : now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="reference">Supplier invoice / ref</label>
                <input class="field" id="reference" name="reference" value="{{ old('reference', $purchase->reference ?? '') }}">
            </div>
            <div class="full">
                <label class="field-label" for="notes">Notes</label>
                <textarea class="field" id="notes" name="notes" rows="2">{{ old('notes', $purchase->notes ?? '') }}</textarea>
            </div>
        </div>

        <h3 style="margin:22px 0 10px;font-size:16px;font-weight:500;">Items received</h3>
        <div class="table-wrap combo-safe">
            <table class="table line-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width:110px;">Qty</th>
                        <th style="width:130px;">Cost</th>
                        <th>Line</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr>
                            <td>
                                <div class="combo" @click.outside="item.open = false">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input
                                        class="field"
                                        :name="`items[${index}][product_name]`"
                                        x-model="item.product_name"
                                        autocomplete="off"
                                        placeholder="Type or select a product"
                                        required
                                        @focus="item.open = true"
                                        @input="onProductInput(index)"
                                        @keydown.arrow-down.prevent="moveProduct(index, 1)"
                                        @keydown.arrow-up.prevent="moveProduct(index, -1)"
                                        @keydown.enter.prevent="pickHighlightedProduct(index)"
                                        @keydown.escape="item.open = false"
                                    >
                                    <div class="combo-list" x-show="item.open && (productChoices(item).length || isNewProduct(item))" x-cloak>
                                        <template x-for="(choice, i) in productChoices(item)" :key="'p-'+choice.id">
                                            <button
                                                type="button"
                                                class="combo-option"
                                                :class="{ active: i === item.highlight }"
                                                @mousedown.prevent="pickProduct(index, choice)"
                                            >
                                                <span x-text="choice.name"></span>
                                                <small x-text="choice.sku"></small>
                                            </button>
                                        </template>
                                        <button
                                            type="button"
                                            class="combo-option combo-create"
                                            x-show="isNewProduct(item)"
                                            @mousedown.prevent="keepNewProduct(index)"
                                        >
                                            Create “<span x-text="item.product_name.trim()"></span>”
                                        </button>
                                    </div>
                                </div>
                                <div class="new-product-fields" x-show="isNewProduct(item)" x-cloak>
                                    <p class="combo-hint">New product — add SKU and selling price.</p>
                                    <div class="new-product-grid">
                                        <div>
                                            <label class="field-label">SKU</label>
                                            <input class="field" :name="isNewProduct(item) ? `items[${index}][sku]` : ''" x-model="item.sku" autocomplete="off">
                                        </div>
                                        <div>
                                            <label class="field-label">Selling price</label>
                                            <input class="field" type="number" step="0.01" min="0" :name="isNewProduct(item) ? `items[${index}][selling_price]` : ''" x-model.number="item.selling_price">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><input class="field" type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required></td>
                            <td><input class="field" type="number" step="0.01" min="0" :name="`items[${index}][cost_price]`" x-model.number="item.cost_price" required></td>
                            <td x-text="money(item.quantity * item.cost_price)"></td>
                            <td><button type="button" class="btn btn-ghost" @click="remove(index)">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline" style="margin-top:12px;" @click="add()">+ Add item</button>
        <div class="totals">
            <div class="totals-row grand"><span>Total</span><span x-text="money(total)"></span></div>
        </div>
