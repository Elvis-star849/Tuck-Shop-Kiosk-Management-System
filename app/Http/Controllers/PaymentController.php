<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice.customer', 'sale'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($invoice) => $invoice->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('sale', fn ($sale) => $sale->where('sale_number', 'like', "%{$search}%"));
                });
            })
            ->latest('payment_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->orderByDesc('invoice_date')
            ->get();

        $selected = $request->integer('invoice_id') ?: null;

        return view('payments.create', compact('invoices', 'selected'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Payment::METHODS))],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice = Invoice::query()->findOrFail($data['invoice_id']);

        if (in_array($invoice->status, ['cancelled', 'draft'], true)) {
            return back()->withInput()->with('error', 'Payments cannot be recorded on draft or cancelled invoices.');
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount > $invoice->balance() + 0.009) {
            return back()->withInput()->with('error', 'Payment exceeds the outstanding balance of '.money($invoice->balance()).'.');
        }

        Payment::query()->create($data);
        $invoice->syncAmountPaid();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment): RedirectResponse
    {
        if ($payment->sale_id) {
            return redirect()->route('sales.show', $payment->sale_id);
        }

        return redirect()->route('invoices.show', $payment->invoice_id);
    }

    public function edit(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;
        $payment->delete();
        $invoice?->syncAmountPaid();

        return back()->with('success', 'Payment removed.');
    }
}
