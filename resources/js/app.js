import './bootstrap';
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('invoiceForm', (products, existingItems, discount) => ({
        products,
        discount: Number(discount || 0),
        items: existingItems.length
            ? existingItems.map((item) => ({
                product_id: item.product_id ? String(item.product_id) : '',
                description: item.description || '',
                quantity: Number(item.quantity || 1),
                unit_price: Number(item.unit_price || 0),
                tax_rate: Number(item.tax_rate || 15),
            }))
            : [],

        stockFor(productId) {
            const product = this.products.find((entry) => String(entry.id) === String(productId));
            return product ? Number(product.quantity || 0) : 0;
        },

        clampQty(index) {
            const item = this.items[index];
            const max = this.stockFor(item.product_id);
            if (max > 0 && Number(item.quantity) > max) {
                item.quantity = max;
                window.alert(`Only ${max} available in stock.`);
            }
        },

        addItem(productId = '') {
            this.items.push({
                product_id: productId ? String(productId) : '',
                description: '',
                quantity: 1,
                unit_price: 0,
                tax_rate: 15,
            });

            if (productId) {
                this.fillProduct(this.items.length - 1);
            }
        },

        addExistingProduct(productId) {
            const stock = this.stockFor(productId);
            const existing = this.items.find((item) => String(item.product_id) === String(productId));
            const used = existing ? Number(existing.quantity || 0) : 0;

            if (stock <= 0) {
                window.alert('This product is out of stock.');
                return;
            }

            if (used + 1 > stock) {
                window.alert(`Insufficient stock. Only ${stock} available.`);
                return;
            }

            if (existing) {
                existing.quantity = used + 1;
                return;
            }

            this.addItem(productId);
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        fillProduct(index) {
            const item = this.items[index];
            const product = this.products.find((entry) => String(entry.id) === String(item.product_id));

            if (! product) {
                item.description = '';
                item.unit_price = 0;
                item.tax_rate = 15;
                return;
            }

            item.description = product.name;
            item.unit_price = Number(product.unit_price);
            item.tax_rate = Number(product.tax_rate);
            this.clampQty(index);
        },

        lineTotal(item) {
            return Number(item.quantity || 0) * Number(item.unit_price || 0);
        },

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },

        get tax() {
            return this.items.reduce((sum, item) => {
                return sum + this.lineTotal(item) * (Number(item.tax_rate || 0) / 100);
            }, 0);
        },

        get total() {
            return Math.max(0, this.subtotal + this.tax - Number(this.discount || 0));
        },

        money(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(value || 0);
        },
    }));

    Alpine.data('posSale', (products, oldItems, discount, paymentMethod, amountPaid, phone) => ({
        products,
        search: '',
        discount: Number(discount || 0),
        paymentMethod: paymentMethod || 'cash',
        amountPaid: Number(amountPaid || 0),
        phone: phone || '',
        cart: (oldItems || []).map((item) => {
            const product = products.find((entry) => String(entry.id) === String(item.product_id));
            return {
                product_id: String(item.product_id),
                name: product?.name || 'Product',
                price: Number(product?.selling_price || 0),
                stock: Number(product?.quantity || 0),
                quantity: Number(item.quantity || 1),
            };
        }),

        get filtered() {
            const term = this.search.trim().toLowerCase();
            if (! term) {
                return this.products;
            }

            return this.products.filter((product) => {
                return [product.name, product.sku, product.barcode]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(term));
            });
        },

        add(product) {
            const stock = Number(product.quantity || 0);
            if (stock <= 0) {
                return;
            }

            const existing = this.cart.find((item) => String(item.product_id) === String(product.id));
            if (existing) {
                if (existing.quantity + 1 > stock) {
                    window.alert(`Insufficient stock. Only ${stock} ${product.name} available.`);
                    return;
                }
                existing.quantity += 1;
                return;
            }

            this.cart.push({
                product_id: String(product.id),
                name: product.name,
                price: Number(product.selling_price),
                stock,
                quantity: 1,
            });

            if (! this.amountPaid) {
                this.amountPaid = this.total;
            }
        },

        clamp(item) {
            if (item.quantity > item.stock) {
                item.quantity = item.stock;
                window.alert(`Only ${item.stock} available.`);
            }
            if (item.quantity < 0.01) {
                item.quantity = 0.01;
            }
        },

        remove(index) {
            this.cart.splice(index, 1);
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + Number(item.quantity) * Number(item.price), 0);
        },

        get total() {
            return Math.max(0, this.subtotal - Number(this.discount || 0));
        },

        get changeDue() {
            return Math.max(0, Number(this.amountPaid || 0) - this.total);
        },

        get completeLabel() {
            return 'Complete sale';
        },

        syncPaid() {
            if (! this.amountPaid) {
                this.amountPaid = this.total;
            }
        },

        payWithEcocash() {
            if (! this.cart.length) {
                return;
            }

            const phone = String(this.phone || '').replace(/\D+/g, '');
            if (! /^07[78]\d{7}$/.test(phone) && ! /^2637[78]\d{7}$/.test(phone)) {
                window.alert('Enter a valid EcoCash number starting with 077 or 078.');
                return;
            }

            this.paymentMethod = 'ecocash';
            this.amountPaid = this.total;
            this.$nextTick(() => this.$refs.saleForm.submit());
        },

        formatQty(value) {
            return Number(value || 0).toString().replace(/\.00$/, '');
        },

        money(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(value || 0);
        },
    }));

    Alpine.data('purchaseForm', (products, suppliers, oldItems, oldSupplierId, oldSupplierName, suggestedSku) => {
        const emptyItem = () => ({
            product_id: '',
            product_name: '',
            sku: '',
            selling_price: '',
            quantity: 1,
            cost_price: 0,
            open: false,
            highlight: 0,
        });

        return {
        products,
        suppliers,
        suggestedSku: suggestedSku || 'SKU-0001',
        skuSeq: 0,
        supplierId: oldSupplierId ? String(oldSupplierId) : '',
        supplierName: oldSupplierName || '',
        supplierOpen: false,
        supplierIndex: 0,
        items: (oldItems && oldItems.length)
            ? oldItems.map((item) => {
                const product = products.find((entry) => String(entry.id) === String(item.product_id));
                return {
                    product_id: item.product_id ? String(item.product_id) : '',
                    product_name: item.product_name || product?.name || '',
                    sku: item.sku || '',
                    selling_price: item.selling_price === undefined || item.selling_price === '' ? '' : Number(item.selling_price),
                    quantity: Number(item.quantity || 1),
                    cost_price: Number(item.cost_price || 0),
                    open: false,
                    highlight: 0,
                };
            })
            : [emptyItem()],

        init() {
            if (this.supplierId && ! this.supplierName) {
                const found = this.suppliers.find((entry) => String(entry.id) === String(this.supplierId));
                if (found) {
                    this.supplierName = found.name;
                }
            }
        },

        emptyItem() {
            return {
                product_id: '',
                product_name: '',
                sku: '',
                selling_price: '',
                quantity: 1,
                cost_price: 0,
                open: false,
                highlight: 0,
            };
        },

        add() {
            this.items.push(this.emptyItem());
        },

        remove(index) {
            this.items.splice(index, 1);
            if (! this.items.length) {
                this.add();
            }
        },

        get isNewSupplier() {
            return this.supplierName.trim() !== '' && ! this.supplierId;
        },

        get supplierChoices() {
            const term = this.supplierName.trim().toLowerCase();
            if (! term) {
                return this.suppliers.slice(0, 8);
            }

            return this.suppliers.filter((entry) => entry.name.toLowerCase().includes(term)).slice(0, 8);
        },

        onSupplierInput() {
            this.supplierOpen = true;
            this.supplierIndex = 0;
            const exact = this.suppliers.find((entry) => entry.name.toLowerCase() === this.supplierName.trim().toLowerCase());
            this.supplierId = exact ? String(exact.id) : '';
        },

        pickSupplier(choice) {
            this.supplierId = String(choice.id);
            this.supplierName = choice.name;
            this.supplierOpen = false;
        },

        keepNewSupplier() {
            this.supplierId = '';
            this.supplierOpen = false;
        },

        moveSupplier(step) {
            const extra = this.isNewSupplier ? 1 : 0;
            const max = this.supplierChoices.length + extra - 1;
            if (max < 0) {
                return;
            }
            this.supplierOpen = true;
            this.supplierIndex = Math.min(max, Math.max(0, this.supplierIndex + step));
        },

        pickHighlightedSupplier() {
            if (! this.supplierOpen) {
                return;
            }
            if (this.supplierIndex < this.supplierChoices.length) {
                this.pickSupplier(this.supplierChoices[this.supplierIndex]);
                return;
            }
            if (this.isNewSupplier) {
                this.keepNewSupplier();
            }
        },

        isNewProduct(item) {
            return item.product_name.trim() !== '' && ! item.product_id;
        },

        productChoices(item) {
            const term = item.product_name.trim().toLowerCase();
            if (! term) {
                return this.products.slice(0, 8);
            }

            return this.products.filter((entry) => {
                return entry.name.toLowerCase().includes(term) || String(entry.sku || '').toLowerCase().includes(term);
            }).slice(0, 8);
        },

        nextSku() {
            const suffix = this.skuSeq;
            this.skuSeq += 1;
            const base = this.suggestedSku.replace(/(\d+)$/, (digits) => {
                return String(Number(digits) + suffix).padStart(digits.length, '0');
            });

            return base;
        },

        onProductInput(index) {
            const item = this.items[index];
            item.open = true;
            item.highlight = 0;
            const exact = this.products.find((entry) => entry.name.toLowerCase() === item.product_name.trim().toLowerCase());
            if (exact) {
                item.product_id = String(exact.id);
                if (! item.cost_price) {
                    item.cost_price = Number(exact.cost_price || 0);
                }
                return;
            }

            item.product_id = '';
            if (item.product_name.trim() && ! item.sku) {
                item.sku = this.nextSku();
            }
        },

        pickProduct(index, choice) {
            const item = this.items[index];
            item.product_id = String(choice.id);
            item.product_name = choice.name;
            item.cost_price = Number(choice.cost_price || 0);
            item.sku = '';
            item.selling_price = '';
            item.open = false;
        },

        keepNewProduct(index) {
            const item = this.items[index];
            item.product_id = '';
            item.open = false;
            if (! item.sku) {
                item.sku = this.nextSku();
            }
            if (item.selling_price === '' && item.cost_price) {
                item.selling_price = Number(item.cost_price);
            }
        },

        moveProduct(index, step) {
            const item = this.items[index];
            const choices = this.productChoices(item);
            const extra = this.isNewProduct(item) ? 1 : 0;
            const max = choices.length + extra - 1;
            if (max < 0) {
                return;
            }
            item.open = true;
            item.highlight = Math.min(max, Math.max(0, (item.highlight || 0) + step));
        },

        pickHighlightedProduct(index) {
            const item = this.items[index];
            if (! item.open) {
                return;
            }
            const choices = this.productChoices(item);
            if (item.highlight < choices.length) {
                this.pickProduct(index, choices[item.highlight]);
                return;
            }
            if (this.isNewProduct(item)) {
                this.keepNewProduct(index);
            }
        },

        get total() {
            return this.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.cost_price || 0), 0);
        },

        money(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(value || 0);
        },
        };
    });
});

window.Alpine = Alpine;
Alpine.start();
