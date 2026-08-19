<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $catalog = $this->catalogQuery($request);

        $products = (clone $catalog)
            ->with(['category', 'supplier'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $totals = (clone $catalog)
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity * cost_price ELSE 0 END), 0) as purchase_total, COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity * selling_price ELSE 0 END), 0) as selling_total')
            ->reorder()
            ->first();

        $purchaseTotal = round((float) ($totals?->purchase_total ?? 0), 2);
        $sellingTotal = round((float) ($totals?->selling_total ?? 0), 2);

        return view('products.index', [
            'products' => $products,
            'purchaseTotal' => $purchaseTotal,
            'sellingTotal' => $sellingTotal,
            'expectedProfit' => round($sellingTotal - $purchaseTotal, 2),
        ]);
    }

    public function create(): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        return redirect()->route('purchases.create')->with('success', 'Add a product, quantity, and purchase price on this page.');
    }

    public function store(): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        return redirect()->route('purchases.create');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'supplier', 'stockMovements.user']);
        $audits = AuditLog::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $product->id)
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('products.show', compact('product', 'audits'));
    }

    public function edit(Product $product): View
    {
        abort_unless($this->isAdmin(), 403);

        $product->load('category');

        return view('products.edit', array_merge($this->formOptions(), compact('product')));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        $product->update($this->validated($request, $product));

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        $product->update(['status' => 'discontinued']);

        return redirect()->route('products.index')->with('success', 'Product discontinued. History is kept.');
    }

    private function catalogQuery(Request $request)
    {
        return Product::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->stock === 'low', fn ($query) => $query->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0))
            ->when($request->stock === 'out', fn ($query) => $query->where('quantity', '<=', 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('products', 'name')->ignore($product?->id),
            ],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category_name' => ['required', 'string', 'max:80'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,discontinued'],
        ]);

        $data['unit_price'] = $data['selling_price'];
        $data['category_id'] = $this->resolveCategory($data['category_id'] ?? null, $data['category_name'])->id;
        unset($data['category_name'], $data['sku'], $data['barcode'], $data['tax_rate'], $data['min_stock'], $data['quantity'], $data['cost_price']);

        return $data;
    }

    private function resolveCategory(mixed $id, string $name): Category
    {
        $name = trim($name);

        if ($id) {
            $category = Category::query()->find($id);
            if ($category && strcasecmp($category->name, $name) === 0) {
                return $category;
            }
        }

        $existing = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $category = Category::query()->create(['name' => $name]);
        AuditLog::record('category.created', 'Admin added category "'.$category->name.'" while saving a product', $category);

        return $category;
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
