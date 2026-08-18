<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $purchases = Purchase::query()->with('supplier')->latest('purchase_date')->paginate(12);

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        return view('purchases.create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'selling_price']),
            'suggestedSku' => Product::nextSku(),
        ]);
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['required_without:supplier_id', 'nullable', 'string', 'max:160'],
            'purchase_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['nullable', 'string', 'max:160'],
            'items.*.sku' => ['nullable', 'string', 'max:40'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertNewProductFields($data['items']);

        $purchase = DB::transaction(function () use ($data, $request, $inventory) {
            $supplier = $this->resolveSupplier($data['supplier_id'] ?? null, $data['supplier_name'] ?? null);

            $total = 0;
            $purchase = Purchase::query()->create([
                'purchase_number' => Purchase::nextNumber(),
                'supplier_id' => $supplier->id,
                'user_id' => $request->user()->id,
                'purchase_date' => $data['purchase_date'],
                'reference' => $data['reference'] ?? null,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $qty = (float) $row['quantity'];
                $cost = (float) $row['cost_price'];
                $line = round($qty * $cost, 2);
                $total += $line;
                $product = $this->resolveProduct($row, $supplier->id);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'cost_price' => $cost,
                    'line_total' => $line,
                ]);

                $product->update(['cost_price' => $cost, 'supplier_id' => $purchase->supplier_id]);

                $inventory->apply(
                    $product,
                    'stock_in',
                    $qty,
                    'Supplier Purchase',
                    $purchase->purchase_number.($purchase->reference ? ' / '.$purchase->reference : ''),
                    Purchase::class,
                    $purchase->id,
                );
            }

            $purchase->update(['total' => $total]);

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase recorded and stock increased.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product', 'user']);

        return view('purchases.show', compact('purchase'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function assertNewProductFields(array $items): void
    {
        $validator = Validator::make([], []);
        $seenSkus = [];

        foreach ($items as $index => $row) {
            if (! empty($row['product_id'])) {
                continue;
            }

            $name = trim((string) ($row['product_name'] ?? ''));
            if ($name === '') {
                $validator->errors()->add("items.$index.product_name", 'Type a product name or pick one from the list.');
                continue;
            }

            if ($this->findProductByName($name)) {
                continue;
            }

            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($sku === '') {
                $validator->errors()->add("items.$index.sku", 'SKU is required for a new product.');
            } elseif (isset($seenSkus[$sku]) || Product::query()->where('sku', $sku)->exists()) {
                $validator->errors()->add("items.$index.sku", 'This SKU is already in use.');
            } else {
                $seenSkus[$sku] = true;
            }

            if (! isset($row['selling_price']) || $row['selling_price'] === '' || $row['selling_price'] === null) {
                $validator->errors()->add("items.$index.selling_price", 'Selling price is required for a new product.');
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    private function resolveSupplier(mixed $id, ?string $name): Supplier
    {
        if ($id) {
            return Supplier::query()->findOrFail($id);
        }

        $name = trim((string) $name);
        $existing = Supplier::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $existing ?? Supplier::query()->create(['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveProduct(array $row, int $supplierId): Product
    {
        if (! empty($row['product_id'])) {
            return Product::query()->findOrFail($row['product_id']);
        }

        $name = trim((string) ($row['product_name'] ?? ''));
        $existing = $this->findProductByName($name);
        if ($existing) {
            return $existing;
        }

        $cost = round((float) $row['cost_price'], 2);
        $selling = round((float) $row['selling_price'], 2);

        return Product::query()->create([
            'sku' => strtoupper(trim((string) $row['sku'])),
            'name' => $name,
            'cost_price' => $cost,
            'selling_price' => $selling,
            'unit_price' => $selling,
            'tax_rate' => (float) config('company.default_tax_rate', 0),
            'quantity' => 0,
            'min_stock' => 5,
            'unit' => 'items',
            'supplier_id' => $supplierId,
            'status' => 'active',
        ]);
    }

    private function findProductByName(string $name): ?Product
    {
        return Product::query()
            ->active()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();
    }
}
