<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function manage(): View
    {
        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->paginate(20);

        return view('stock.manage', compact('products'));
    }

    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($request->product_id, fn ($query, $id) => $query->where('product_id', $id))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('stock.history', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function createIn(): View
    {
        return view('stock.in', [
            'products' => Product::query()->active()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function storeIn(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        if (isset($data['cost_price'])) {
            $product->update(['cost_price' => $data['cost_price']]);
        }

        $movement = $inventory->apply(
            $product,
            'stock_in',
            (float) $data['quantity'],
            $data['reason'] ?: 'Supplier Purchase',
            $data['notes'] ?? null,
        );

        return redirect()->route('stock.history')->with(
            'success',
            $product->name.': old stock '.$movement->quantity_before.', added +'.$data['quantity'].', new stock '.$movement->quantity_after.'.'
        );
    }

    public function createOut(): View
    {
        return view('stock.out', [
            'products' => Product::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function storeOut(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'in:'.implode(',', array_keys(StockMovement::OUT_REASONS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $type = match ($data['reason']) {
            'damaged' => 'damaged',
            'expired' => 'expired',
            'lost' => 'lost',
            'return_supplier' => 'adjustment',
            default => 'adjustment',
        };

        try {
            $movement = $inventory->apply(
                Product::query()->findOrFail($data['product_id']),
                $type,
                (float) $data['quantity'],
                StockMovement::OUT_REASONS[$data['reason']],
                $data['notes'] ?? null,
            );
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('stock.history')->with(
            'success',
            'Removed '.$data['quantity'].'. Old stock '.$movement->quantity_before.', new stock '.$movement->quantity_after.'.'
        );
    }

    public function createAdjust(): View
    {
        return view('stock.adjust', [
            'products' => Product::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function storeAdjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'actual_quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:120'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);

        try {
            $movement = $inventory->adjustTo(
                $product,
                (float) $data['actual_quantity'],
                $data['reason'],
                $data['notes'] ?? null,
            );
        } catch (InsufficientStockException|\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        \App\Models\AuditLog::record(
            'stock.adjusted',
            'Admin adjusted '.$product->name.' stock from '.$movement->quantity_before.' to '.$movement->quantity_after,
            $product,
            'quantity',
            $movement->quantity_before,
            $movement->quantity_after,
        );

        return redirect()->route('stock.history')->with(
            'success',
            $product->name.': system '.$movement->quantity_before.', actual '.$movement->quantity_after.', difference '.($movement->quantity_after - $movement->quantity_before).'.'
        );
    }

    public function expired(): View
    {
        $expired = Product::query()
            ->active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now())
            ->orderBy('expiry_date')
            ->get();

        $expiringSoon = Product::query()
            ->active()
            ->expiringSoon()
            ->orderBy('expiry_date')
            ->get();

        return view('stock.expired', compact('expired', 'expiringSoon'));
    }

    public function low(): View
    {
        $products = Product::query()
            ->active()
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity')
            ->paginate(20);

        return view('stock.low', compact('products'));
    }
}
