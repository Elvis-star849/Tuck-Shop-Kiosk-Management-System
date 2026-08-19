        <div class="form-grid purchase-meta">
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
                        placeholder="Type or select"
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
            </div>
            <div>
                <label class="field-label" for="purchase_date">Date</label>
                <input class="field" id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', isset($purchase) ? $purchase->purchase_date->toDateString() : now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="reference">Invoice / ref</label>
                <input class="field" id="reference" name="reference" value="{{ old('reference', $purchase->reference ?? '') }}" placeholder="Optional">
            </div>
            <div>
                <label class="field-label" for="notes">Notes</label>
                <input class="field" id="notes" name="notes" value="{{ old('notes', $purchase->notes ?? '') }}" placeholder="Optional">
            </div>
        </div>

        <div class="purchase-items-head">
            <h3>Items received</h3>
            <button type="button" class="btn btn-outline" @click="add()">+ Add item</button>
        </div>
        <div class="table-wrap combo-safe">
            <table class="table line-table purchase-lines">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width:190px;">Category</th>
                        <th style="width:84px;">Qty</th>
                        <th style="width:100px;">Cost</th>
                        <th style="width:100px;">Selling</th>
                        <th style="width:90px;">Line</th>
                        <th style="width:44px;"></th>
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
                                                <small x-text="choice.category_name || ''"></small>
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
                            </td>
                            <td>
                                <div class="combo" @click.outside="item.category_open = false">
                                    <input type="hidden" :name="`items[${index}][category_id]`" :value="item.category_id">
                                    <input
                                        class="field"
                                        :name="`items[${index}][category_name]`"
                                        x-model="item.category_name"
                                        autocomplete="off"
                                        placeholder="Type or select"
                                        :required="isNewProduct(item)"
                                        @focus="item.category_open = true"
                                        @input="onCategoryInput(index)"
                                        @keydown.arrow-down.prevent="moveCategory(index, 1)"
                                        @keydown.arrow-up.prevent="moveCategory(index, -1)"
                                        @keydown.enter.prevent="pickHighlightedCategory(index)"
                                        @keydown.escape="item.category_open = false"
                                    >
                                    <div class="combo-list" x-show="item.category_open && (categoryChoices(item).length || isNewCategory(item))" x-cloak>
                                        <template x-for="(choice, i) in categoryChoices(item)" :key="'c-'+choice.id">
                                            <button
                                                type="button"
                                                class="combo-option"
                                                :class="{ active: i === item.category_highlight }"
                                                @mousedown.prevent="pickCategory(index, choice)"
                                            >
                                                <span x-text="choice.name"></span>
                                            </button>
                                        </template>
                                        <button
                                            type="button"
                                            class="combo-option combo-create"
                                            x-show="isNewCategory(item)"
                                            @mousedown.prevent="keepNewCategory(index)"
                                        >
                                            Create “<span x-text="(item.category_name || '').trim()"></span>”
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td><input class="field" type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required></td>
                            <td><input class="field" type="number" step="0.01" min="0" :name="`items[${index}][cost_price]`" x-model.number="item.cost_price" @input="onCostInput(index)" required></td>
                            <td><input class="field" type="number" step="0.01" min="0" :name="`items[${index}][selling_price]`" x-model.number="item.selling_price" required></td>
                            <td x-text="money(item.quantity * item.cost_price)"></td>
                            <td><button type="button" class="btn btn-ghost" title="Remove" @click="remove(index)">×</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="totals">
            <div class="totals-row grand"><span>Total</span><span x-text="money(total)"></span></div>
        </div>
