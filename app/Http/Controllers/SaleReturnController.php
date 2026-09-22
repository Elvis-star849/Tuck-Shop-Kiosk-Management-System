<?php

namespace App\Http\Controllers;

use App\Exceptions\SaleReturnException;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function index(Request $request, SaleReturnService $returns): View
    {
        $pending = collect();
        if ($request->user()->isAdmin()) {
            $pending = SaleReturn::query()
                ->requested()
                ->with(['sale', 'requestedBy', 'items.saleItem'])
                ->latest()
                ->get();
        } else {
            $pending = SaleReturn::query()
                ->requested()
                ->where('requested_by', $request->user()->id)
                ->with(['sale', 'items.saleItem'])
                ->latest()
                ->get();
        }

        $recent = SaleReturn::query()
            ->with(['sale', 'requestedBy'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('requested_by', $request->user()->id))
            ->latest()
            ->limit(10)
            ->get();

        return view('returns.index', [
            'number' => $returns->normalizeReceiptNumber((string) $request->input('number', '')),
            'pending' => $pending,
            'recent' => $recent,
        ]);
    }

    public function lookup(Request $request, SaleReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
        ]);

        try {
            $sale = $returns->findSale($data['number']);
        } catch (SaleReturnException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('returns.sale', ['number' => $sale->sale_number]);
    }

    public function create(Request $request, SaleReturnService $returns): View|RedirectResponse
    {
        $number = (string) $request->query('number', '');

        try {
            $sale = $returns->findSale($number);
        } catch (SaleReturnException $exception) {
            return redirect()->route('returns.index')->with('error', $exception->getMessage());
        }

        return view('returns.create', compact('sale'));
    }

    public function store(Request $request, SaleReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = Sale::query()->findOrFail($data['sale_id']);

        try {
            $return = $returns->create($sale, $data['items'], $data['reason'], $request->user());
        } catch (SaleReturnException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        if ($return->isApproved()) {
            return redirect()->route('sales.show', $sale)->with(
                'success',
                $return->return_number.': refunded '.money((float) $return->refund_amount).' and stock restored.'
            );
        }

        return redirect()->route('returns.index')->with(
            'success',
            $return->return_number.' is waiting for admin approval. Stock and money have not moved yet.'
        );
    }

    public function approve(Request $request, SaleReturn $saleReturn, SaleReturnService $returns): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $return = $returns->approve($saleReturn, $request->user());
        } catch (SaleReturnException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('returns.index')->with(
            'success',
            $return->return_number.' approved. '.money((float) $return->refund_amount).' refunded and stock restored.'
        );
    }

    public function reject(Request $request, SaleReturn $saleReturn, SaleReturnService $returns): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $return = $returns->reject($saleReturn, $request->user());
        } catch (SaleReturnException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('returns.index')->with('success', $return->return_number.' rejected.');
    }
}
