<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'supplier'])
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
            ->when($request->stock === 'out', fn ($query) => $query->where('quantity', '<=', 0))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        abort_unless($this->isAdmin(), 403);

        return view('products.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isAdmin(), 403);

        $product = Product::query()->create($this->validated($request));
        AuditLog::record('product.created', 'Admin added product "'.$product->name.'"', $product);

        return redirect()->route('products.index')->with('success', 'Product saved.');
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
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku,'.($product?->id ?? 'NULL')],
            'barcode' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,discontinued'],
        ]);

        $data['unit_price'] = $data['selling_price'];

        return $data;
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
