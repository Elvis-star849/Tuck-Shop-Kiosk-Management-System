<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\InvoiceCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@chindeka.test'],
            [
                'name' => 'Tashinga Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'cashier@chindeka.test'],
            [
                'name' => 'Rudo Cashier',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'cashier',
            ]
        );

        Auth::login($admin);

        $drinks = Category::query()->updateOrCreate(['name' => 'Drinks']);
        $food = Category::query()->updateOrCreate(['name' => 'Food']);
        $groceries = Category::query()->updateOrCreate(['name' => 'Groceries']);

        $supplier = Supplier::query()->updateOrCreate(
            ['name' => 'ABC Wholesale'],
            [
                'contact_name' => 'Amina Chipo',
                'email' => 'sales@abcwholesale.test',
                'phone' => '+263 77 111 2222',
                'address' => 'Mbare Musika, Harare',
            ]
        );

        $catalog = collect([
            ['sku' => 'COKE-500', 'name' => 'Coca Cola 500ml', 'category_id' => $drinks->id, 'cost_price' => 0.60, 'selling_price' => 1.00, 'min_stock' => 10, 'qty' => 48],
            ['sku' => 'BREAD-1', 'name' => 'Bread', 'category_id' => $food->id, 'cost_price' => 0.80, 'selling_price' => 1.20, 'min_stock' => 8, 'qty' => 24],
            ['sku' => 'MAPUTI-1', 'name' => 'Maputi', 'category_id' => $food->id, 'cost_price' => 0.20, 'selling_price' => 0.50, 'min_stock' => 15, 'qty' => 8],
            ['sku' => 'MILK-1L', 'name' => 'Milk 1L', 'category_id' => $groceries->id, 'cost_price' => 1.10, 'selling_price' => 1.80, 'min_stock' => 6, 'qty' => 10],
            ['sku' => 'SUGAR-2KG', 'name' => 'Sugar 2kg', 'category_id' => $groceries->id, 'cost_price' => 2.00, 'selling_price' => 2.80, 'min_stock' => 10, 'qty' => 4],
        ])->map(function (array $row) use ($supplier) {
            $qty = $row['qty'];
            unset($row['qty']);

            $product = Product::query()->firstOrCreate(
                ['sku' => $row['sku']],
                $row + [
                    'supplier_id' => $supplier->id,
                    'unit_price' => $row['selling_price'],
                    'tax_rate' => 0,
                    'unit' => 'items',
                    'status' => 'active',
                    'quantity' => 0,
                ]
            );

            return ['product' => $product->fresh(), 'qty' => $qty];
        });

        $inventory = app(InventoryService::class);

        foreach ($catalog as $row) {
            if ((float) $row['product']->quantity < $row['qty']) {
                $inventory->apply(
                    $row['product'],
                    'stock_in',
                    $row['qty'] - (float) $row['product']->quantity,
                    'Opening Stock',
                    'Seed opening stock',
                    null,
                    null,
                    $admin->id,
                );
            }
        }

        $products = $catalog->mapWithKeys(fn ($row) => [$row['product']->sku => $row['product']->fresh()]);

        $purchase = Purchase::query()->create([
            'purchase_number' => Purchase::nextNumber(),
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'purchase_date' => now()->subDays(2)->toDateString(),
            'reference' => 'ABC-4412',
            'total' => 0,
            'notes' => 'Weekly restock from ABC Wholesale',
        ]);

        $purchaseLines = [
            ['sku' => 'COKE-500', 'qty' => 12, 'cost' => 0.60],
            ['sku' => 'BREAD-1', 'qty' => 10, 'cost' => 0.80],
        ];
        $purchaseTotal = 0;
        foreach ($purchaseLines as $line) {
            $product = $products[$line['sku']];
            $total = round($line['qty'] * $line['cost'], 2);
            $purchaseTotal += $total;
            $purchase->items()->create([
                'product_id' => $product->id,
                'quantity' => $line['qty'],
                'cost_price' => $line['cost'],
                'line_total' => $total,
            ]);
            $inventory->apply(
                $product,
                'stock_in',
                $line['qty'],
                'Supplier Purchase',
                $purchase->purchase_number,
                Purchase::class,
                $purchase->id,
                $admin->id,
            );
        }
        $purchase->update(['total' => $purchaseTotal]);

        $this->makeSale($admin, $inventory, [
            ['sku' => 'COKE-500', 'qty' => 2],
            ['sku' => 'BREAD-1', 'qty' => 1],
        ], 'cash', 5, now()->subHours(3));

        $this->makeSale($admin, $inventory, [
            ['sku' => 'MAPUTI-1', 'qty' => 3],
            ['sku' => 'MILK-1L', 'qty' => 1],
        ], 'ecocash', 3.30, now()->subHours(1));

        $this->makeSale($admin, $inventory, [
            ['sku' => 'SUGAR-2KG', 'qty' => 1],
            ['sku' => 'COKE-500', 'qty' => 1],
        ], 'card', 4.00, now()->subMinutes(20));

        Expense::query()->updateOrCreate(
            ['description' => 'Kombi to Mbare for stock', 'expense_date' => today()],
            ['category' => 'Transport', 'amount' => 3.00, 'user_id' => $admin->id]
        );
        Expense::query()->updateOrCreate(
            ['description' => 'Shop electricity token', 'expense_date' => today()->subDay()],
            ['category' => 'Electricity', 'amount' => 12.00, 'user_id' => $admin->id]
        );

        $customers = collect([
            ['name' => 'Amina Chipo', 'company_name' => 'ABC Company', 'email' => 'accounts@abc.test', 'phone' => '+263 77 111 2222', 'address' => 'Harare, Zimbabwe'],
            ['name' => 'Tendai Moyo', 'company_name' => 'Moyo Logistics', 'email' => 'tendai@moyo.test', 'phone' => '+263 77 333 4444', 'address' => 'Bulawayo, Zimbabwe'],
        ])->map(fn (array $data) => Customer::query()->updateOrCreate(['email' => $data['email']], $data));

        $this->makeInvoice($admin, $customers[0], now()->toDateString(), now()->addDays(7)->toDateString(), 0, 'draft', [
            ['product' => $products['COKE-500']->fresh(), 'qty' => 10],
            ['product' => $products['BREAD-1']->fresh(), 'qty' => 5],
        ], false);

        $this->makeInvoice($admin, $customers[1], now()->subDays(10)->toDateString(), now()->subDays(3)->toDateString(), 0, 'overdue', [
            ['product' => $products['SUGAR-2KG']->fresh(), 'qty' => 2],
        ], false, null, 0, $inventory);
    }

    /**
     * @param  array<int, array{sku: string, qty: float|int}>  $lines
     */
    private function makeSale(User $user, InventoryService $inventory, array $lines, string $method, float $amountPaid, $soldAt): void
    {
        $subtotal = 0;
        $resolved = [];

        foreach ($lines as $line) {
            $product = Product::query()->where('sku', $line['sku'])->firstOrFail();
            $qty = (float) $line['qty'];
            $lineTotal = round($qty * (float) $product->selling_price, 2);
            $subtotal += $lineTotal;
            $resolved[] = compact('product', 'qty', 'lineTotal');
        }

        $sale = Sale::query()->create([
            'sale_number' => Sale::nextNumber(),
            'user_id' => $user->id,
            'sold_at' => $soldAt,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'amount_paid' => $amountPaid,
            'change_due' => round($amountPaid - $subtotal, 2),
            'payment_method' => $method,
            'status' => 'completed',
        ]);

        foreach ($resolved as $line) {
            $sale->items()->create([
                'product_id' => $line['product']->id,
                'description' => $line['product']->name,
                'quantity' => $line['qty'],
                'unit_price' => $line['product']->selling_price,
                'cost_price' => $line['product']->cost_price,
                'line_total' => $line['lineTotal'],
            ]);

            $inventory->apply(
                $line['product'],
                'sale',
                $line['qty'],
                'POS sale',
                $sale->sale_number,
                Sale::class,
                $sale->id,
                $user->id,
            );
        }

        Payment::query()->create([
            'sale_id' => $sale->id,
            'invoice_id' => null,
            'amount' => $subtotal,
            'payment_method' => $method,
            'payment_reference' => $sale->sale_number,
            'payment_date' => $soldAt->toDateString(),
            'notes' => 'POS '.$sale->sale_number,
        ]);
    }

    /**
     * @param  array<int, array{product: Product, qty: float|int}>  $lines
     */
    private function makeInvoice(
        User $user,
        Customer $customer,
        string $date,
        string $due,
        float $discount,
        string $status,
        array $lines,
        bool $withPayment = false,
        ?string $paymentDate = null,
        float $paymentAmount = 0,
        ?InventoryService $inventory = null,
    ): void {
        $items = collect($lines)->map(fn (array $line) => [
            'product_id' => $line['product']->id,
            'description' => $line['product']->name,
            'quantity' => $line['qty'],
            'unit_price' => $line['product']->unit_price,
            'tax_rate' => $line['product']->tax_rate,
        ])->all();

        $totals = InvoiceCalculator::calculate($items, $discount);

        $invoice = Invoice::query()->create([
            'invoice_number' => Invoice::nextNumber(),
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'invoice_date' => $date,
            'due_date' => $due,
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
            'amount_paid' => 0,
            'status' => $status === 'draft' ? 'draft' : 'sent',
            'notes' => $status === 'draft' ? 'Credit sale draft for a regular customer.' : null,
        ]);

        $invoice->items()->createMany($totals['items']);

        if ($status !== 'draft' && $inventory) {
            $inventory->deductForInvoice($invoice);
        }

        if ($withPayment) {
            $amount = $paymentAmount > 0
                ? min($paymentAmount, $totals['total'])
                : $totals['total'];

            Payment::query()->create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'PAY-'.$invoice->id,
                'payment_date' => $paymentDate ?? $date,
            ]);
            $invoice->syncAmountPaid();
        } else {
            $invoice->status = $status;
            $invoice->save();
        }
    }
}
