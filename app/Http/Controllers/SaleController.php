<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with('user')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest('sold_at')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);
        $sale->load(['items.product', 'user', 'payments', 'cancelledBy', 'gatewayTransactions']);

        return view('sales.show', compact('sale'));
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);
        $sale->load(['items', 'user']);

        return view('sales.receipt', compact('sale'));
    }

    public function downloadPdf(Request $request, Sale $sale): Response
    {
        $this->authorizeSale($request, $sale);
        $sale->load(['items', 'user']);
        $pdf = Pdf::loadView('sales.pdf', compact('sale'))->setPaper('a5');

        return $pdf->download($sale->sale_number.'.pdf');
    }

    public function requestCancel(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        if (! $sale->isCompleted()) {
            return back()->with('error', 'Only a completed sale can be cancelled.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        $sale->update([
            'status' => 'cancel_requested',
            'cancel_reason' => $data['cancel_reason'],
            'cancel_requested_at' => now(),
        ]);

        AuditLog::record(
            'sale.cancel_requested',
            $request->user()->name.' requested cancellation of '.$sale->sale_number,
            $sale,
        );

        return back()->with('success', 'Cancellation requested. An admin will approve or reject it.');
    }

    public function approveCancel(Request $request, Sale $sale, InventoryService $inventory): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (! in_array($sale->status, ['completed', 'cancel_requested'], true)) {
            return back()->with('error', 'This sale cannot be cancelled.');
        }

        $inventory->restoreForSale($sale);
        $sale->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancel_reason' => $sale->cancel_reason ?: 'Cancelled by admin',
        ]);

        AuditLog::record(
            'sale.cancelled',
            'Admin approved cancellation of '.$sale->sale_number.' and restored stock',
            $sale,
        );

        return back()->with('success', 'Sale cancelled and stock restored.');
    }

    public function rejectCancel(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (! $sale->isCancelRequested()) {
            return back()->with('error', 'There is no cancellation request to reject.');
        }

        $sale->update([
            'status' => 'completed',
            'cancel_requested_at' => null,
        ]);

        AuditLog::record(
            'sale.cancel_rejected',
            'Admin rejected cancellation of '.$sale->sale_number,
            $sale,
        );

        return back()->with('success', 'Cancellation request rejected. Sale stays completed.');
    }

    public function voidPending(Request $request, Sale $sale, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        if (! $sale->isPendingPayment()) {
            return back()->with('error', 'Only a sale waiting for payment can be voided.');
        }

        $inventory->restoreForSale($sale);
        $sale->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancel_reason' => 'Unpaid gateway sale voided',
        ]);

        AuditLog::record(
            'sale.voided',
            $request->user()->name.' voided unpaid '.$sale->sale_number.' and restored stock',
            $sale,
        );

        return redirect()->route('sales.index')->with('success', 'Unpaid sale voided and stock restored.');
    }

    private function authorizeSale(Request $request, Sale $sale): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless((int) $sale->user_id === (int) $request->user()->id, 403, 'You can only view your own sales.');
    }
}
