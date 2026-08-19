<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
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
        $grandTotal = (float) Purchase::query()->sum('total');

        return view('purchases.index', compact('purchases', 'grandTotal'));
    }

    public function create(): View
    {
        return view('purchases.create', $this->formCatalog());
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $this->validatedPurchase($request);

        try {
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
                    $line = $this->buildLine($row, $supplier->id);
                    $total += $line['line_total'];

                    $purchase->items()->create([
                        'product_id' => $line['product']->id,
                        'quantity' => $line['qty'],
                        'cost_price' => $line['cost'],
                        'line_total' => $line['line_total'],
                    ]);

                    $line['product']->update(['cost_price' => $line['cost'], 'supplier_id' => $purchase->supplier_id]);

                    $inventory->apply(
                        $line['product'],
                        'stock_in',
                        $line['qty'],
                        'Supplier Purchase',
                        $purchase->purchase_number.($purchase->reference ? ' / '.$purchase->reference : ''),
                        Purchase::class,
                        $purchase->id,
                    );
                }

                $purchase->update(['total' => $total]);

                return $purchase;
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase recorded and stock increased.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product', 'user']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product.category']);

        $products = Product::query()
            ->with('category:id,name')
            ->where(function ($query) use ($purchase) {
                $query->where('status', 'active')
                    ->orWhereIn('id', $purchase->items->pluck('product_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'selling_price', 'category_id']);

        return view('purchases.edit', array_merge($this->formCatalog($products), [
            'purchase' => $purchase,
            'itemDefaults' => $purchase->items->map(fn (PurchaseItem $item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'cost_price' => (float) $item->cost_price,
                'selling_price' => (float) ($item->product?->selling_price ?? 0),
                'category_id' => $item->product?->category_id,
                'category_name' => $item->product?->category?->name,
            ])->values()->all(),
        ]));
    }

    public function update(Request $request, Purchase $purchase, InventoryService $inventory): RedirectResponse
    {
        $data = $this->validatedPurchase($request);

        try {
            DB::transaction(function () use ($data, $purchase, $inventory) {
                $purchase->load('items');
                $oldItems = $purchase->items;
                $supplier = $this->resolveSupplier($data['supplier_id'] ?? null, $data['supplier_name'] ?? null);

                $newLines = [];
                foreach ($data['items'] as $row) {
                    $newLines[] = $this->buildLine($row, $supplier->id);
                }

                $this->syncPurchaseStock($oldItems, $newLines, $inventory, $purchase);

                $purchase->items()->delete();
                $total = 0;
                foreach ($newLines as $line) {
                    $total += $line['line_total'];
                    $purchase->items()->create([
                        'product_id' => $line['product']->id,
                        'quantity' => $line['qty'],
                        'cost_price' => $line['cost'],
                        'line_total' => $line['line_total'],
                    ]);
                    $line['product']->update(['cost_price' => $line['cost'], 'supplier_id' => $supplier->id]);
                }

                $purchase->update([
                    'supplier_id' => $supplier->id,
                    'purchase_date' => $data['purchase_date'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'total' => $total,
                ]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        AuditLog::record(
            'purchase.updated',
            $request->user()->name.' updated '.$purchase->purchase_number,
            $purchase,
        );

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated and stock adjusted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPurchase(Request $request): array
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
            'items.*.category_id' => ['nullable', 'exists:categories,id'],
            'items.*.category_name' => ['nullable', 'string', 'max:80'],
            'items.*.sku' => ['nullable', 'string', 'max:40'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertNewProductFields($data['items']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{product: Product, qty: float, cost: float, line_total: float}
     */
    private function buildLine(array $row, int $supplierId): array
    {
        $qty = (float) $row['quantity'];
        $cost = (float) $row['cost_price'];

        return [
            'product' => $this->resolveProduct($row, $supplierId),
            'qty' => $qty,
            'cost' => $cost,
            'line_total' => round($qty * $cost, 2),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PurchaseItem>  $oldItems
     * @param  array<int, array{product: Product, qty: float, cost: float, line_total: float}>  $newLines
     */
    private function syncPurchaseStock($oldItems, array $newLines, InventoryService $inventory, Purchase $purchase): void
    {
        $oldByProduct = [];
        foreach ($oldItems as $item) {
            $id = (int) $item->product_id;
            $oldByProduct[$id] = ($oldByProduct[$id] ?? 0) + (float) $item->quantity;
        }

        $newByProduct = [];
        foreach ($newLines as $line) {
            $id = (int) $line['product']->id;
            $newByProduct[$id] = ($newByProduct[$id] ?? 0) + $line['qty'];
        }

        $note = $purchase->purchase_number.' edited';
        $ids = array_unique([...array_keys($oldByProduct), ...array_keys($newByProduct)]);

        foreach ($ids as $id) {
            $diff = round(($newByProduct[$id] ?? 0) - ($oldByProduct[$id] ?? 0), 2);
            if ($diff == 0.0) {
                continue;
            }

            $product = Product::query()->findOrFail($id);
            if ($diff > 0) {
                $inventory->apply(
                    $product,
                    'stock_in',
                    $diff,
                    'Purchase edited',
                    $note,
                    Purchase::class,
                    $purchase->id,
                );
            } else {
                $inventory->apply(
                    $product,
                    'adjustment',
                    abs($diff),
                    'Purchase edited',
                    $note,
                    Purchase::class,
                    $purchase->id,
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function assertNewProductFields(array $items): void
    {
        $validator = Validator::make([], []);

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

            $categoryName = trim((string) ($row['category_name'] ?? ''));
            if ($categoryName === '' && empty($row['category_id'])) {
                $validator->errors()->add("items.$index.category_name", 'Category is required for a new product.');
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

    private function resolveCategory(mixed $id, ?string $name): Category
    {
        $name = trim((string) $name);

        if ($id) {
            $category = Category::query()->find($id);
            if ($category && ($name === '' || strcasecmp($category->name, $name) === 0)) {
                return $category;
            }
        }

        if ($name === '') {
            throw ValidationException::withMessages([
                'items' => 'Category is required for a new product.',
            ]);
        }

        $existing = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $category = Category::query()->create(['name' => $name]);
        AuditLog::record('category.created', 'Admin added category "'.$category->name.'" while recording a purchase', $category);

        return $category;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>|null  $products
     * @return array<string, mixed>
     */
    private function formCatalog($products = null): array
    {
        $products ??= Product::query()
            ->active()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'selling_price', 'category_id']);

        return [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'cost_price' => (float) $product->cost_price,
                'selling_price' => (float) $product->selling_price,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
            ])->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveProduct(array $row, int $supplierId): Product
    {
        if (! empty($row['product_id'])) {
            $product = Product::query()->findOrFail($row['product_id']);
            $this->applyCategoryToProduct($product, $row);
            $this->applySellingToProduct($product, $row);

            return $product;
        }

        $name = trim((string) ($row['product_name'] ?? ''));
        $existing = $this->findProductByName($name);
        if ($existing) {
            $this->applyCategoryToProduct($existing, $row);
            $this->applySellingToProduct($existing, $row);

            return $existing;
        }

        $cost = round((float) $row['cost_price'], 2);
        $selling = isset($row['selling_price']) && $row['selling_price'] !== '' && $row['selling_price'] !== null
            ? round((float) $row['selling_price'], 2)
            : $cost;
        $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if ($sku === '') {
            $sku = Product::nextSku();
        }

        return Product::query()->create([
            'sku' => $sku,
            'name' => $name,
            'category_id' => $this->resolveCategory($row['category_id'] ?? null, $row['category_name'] ?? '')->id,
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

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyCategoryToProduct(Product $product, array $row): void
    {
        $name = trim((string) ($row['category_name'] ?? ''));
        if ($name === '' && empty($row['category_id'])) {
            return;
        }

        $product->update([
            'category_id' => $this->resolveCategory($row['category_id'] ?? null, $name)->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applySellingToProduct(Product $product, array $row): void
    {
        if (! isset($row['selling_price']) || $row['selling_price'] === '' || $row['selling_price'] === null) {
            return;
        }

        $selling = round((float) $row['selling_price'], 2);
        $product->update([
            'selling_price' => $selling,
            'unit_price' => $selling,
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
