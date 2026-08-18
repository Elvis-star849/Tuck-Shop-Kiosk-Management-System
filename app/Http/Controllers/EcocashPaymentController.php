<?php

namespace App\Http\Controllers;

use App\Models\GatewayTransaction;
use App\Models\Invoice;
use App\Models\Sale;
use App\Services\PaynowEcocashGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class EcocashPaymentController extends Controller
{
    public function create(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->balance() <= 0 || in_array($invoice->status, ['draft', 'cancelled', 'paid'], true)) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice cannot be paid with EcoCash.');
        }

        return view('payments.ecocash', [
            'invoice' => $invoice->load('customer'),
            'sale' => null,
            'simulating' => app(PaynowEcocashGateway::class)->simulating(),
        ]);
    }

    public function store(Request $request, Invoice $invoice, PaynowEcocashGateway $gateway): RedirectResponse
    {
        if ($invoice->balance() <= 0 || in_array($invoice->status, ['draft', 'cancelled', 'paid'], true)) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice cannot be paid with EcoCash.');
        }

        return $this->startEcocash($request, $invoice, $invoice->balance(), $gateway);
    }

    public function createForSale(Request $request, Sale $sale): View|RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        if (! $sale->isPendingPayment() || $sale->balance() <= 0) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale is not waiting for an EcoCash payment.');
        }

        return view('payments.ecocash', [
            'invoice' => null,
            'sale' => $sale,
            'simulating' => app(PaynowEcocashGateway::class)->simulating(),
        ]);
    }

    public function storeForSale(Request $request, Sale $sale, PaynowEcocashGateway $gateway): RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        if (! $sale->isPendingPayment() || $sale->balance() <= 0) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale is not waiting for an EcoCash payment.');
        }

        return $this->startEcocash($request, $sale, $sale->balance(), $gateway);
    }

    public function startPaynowForSale(Request $request, Sale $sale, PaynowEcocashGateway $gateway): RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        if (! $sale->isPendingPayment() || $sale->balance() <= 0) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'This sale is not waiting for a Paynow payment.');
        }

        try {
            $transaction = $gateway->initiate($sale, $sale->balance(), 'paynow');
        } catch (Throwable $exception) {
            return redirect()->route('sales.show', $sale)->with('error', $exception->getMessage());
        }

        return $this->afterInitiate($transaction, $gateway);
    }

    public function show(GatewayTransaction $transaction): View
    {
        $transaction->load(['invoice.customer', 'sale']);

        return view('payments.ecocash-waiting', [
            'transaction' => $transaction,
            'simulating' => app(PaynowEcocashGateway::class)->simulating(),
        ]);
    }

    public function poll(GatewayTransaction $transaction, PaynowEcocashGateway $gateway): JsonResponse
    {
        $transaction = $gateway->refresh($transaction);

        return response()->json([
            'status' => $transaction->status,
            'paid' => $transaction->status === 'paid',
            'message' => $this->statusMessage($transaction),
            'redirect' => $transaction->status === 'paid'
                ? $transaction->paidRedirectRoute()
                : null,
        ]);
    }

    public function returnFromPaynow(GatewayTransaction $transaction, PaynowEcocashGateway $gateway): RedirectResponse
    {
        $transaction = $gateway->refresh($transaction);

        if ($transaction->status === 'paid') {
            return redirect()->to($transaction->paidRedirectRoute())
                ->with('success', 'Payment received.');
        }

        return redirect()->route('payments.ecocash.show', $transaction)
            ->with('error', $this->statusMessage($transaction));
    }

    public function webhook(Request $request, PaynowEcocashGateway $gateway): Response
    {
        $gateway->handleWebhook($request->all());

        return response('OK', 200);
    }

    public function simulate(GatewayTransaction $transaction, PaynowEcocashGateway $gateway): RedirectResponse
    {
        try {
            $gateway->simulateConfirm($transaction);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->to($transaction->fresh()->paidRedirectRoute())
            ->with('success', 'Demo payment recorded.');
    }

    public function afterInitiate(GatewayTransaction $transaction, PaynowEcocashGateway $gateway): RedirectResponse
    {
        if ($transaction->isPaynowWeb() && $transaction->redirectUrl() && ! $gateway->simulating()) {
            return redirect()->away($transaction->redirectUrl());
        }

        $message = $transaction->isPaynowWeb()
            ? 'Paynow checkout started. Complete payment on the Paynow page.'
            : 'EcoCash PIN prompt sent. Ask the customer to approve the payment on their phone.';

        return redirect()->route('payments.ecocash.show', $transaction)->with('success', $message);
    }

    private function startEcocash(Request $request, Invoice|Sale $payable, float $balance, PaynowEcocashGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'phone' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $amount = round((float) $data['amount'], 2);
        if ($amount > $balance + 0.009) {
            return back()->withInput()->with('error', 'Amount cannot exceed the outstanding balance of '.money($balance).'.');
        }

        $phone = $gateway->normalizePhone($data['phone']);
        if (! preg_match('/^07[78]\d{7}$/', $phone)) {
            return back()->withInput()->with('error', 'Enter a valid EcoCash number starting with 077 or 078.');
        }

        try {
            $transaction = $gateway->initiate($payable, $amount, 'ecocash', $phone);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return $this->afterInitiate($transaction, $gateway);
    }

    private function authorizeSale(Request $request, Sale $sale): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless((int) $sale->user_id === (int) $request->user()->id, 403, 'You can only take payment on your own sales.');
    }

    private function statusMessage(GatewayTransaction $transaction): string
    {
        return match ($transaction->status) {
            'paid' => 'Payment received.',
            'cancelled' => 'The payment was cancelled.',
            'failed' => $transaction->error_message ?: 'Payment failed.',
            default => $transaction->isPaynowWeb()
                ? 'Waiting for the customer to finish paying on Paynow.'
                : 'Waiting for the customer to enter their EcoCash PIN.',
        };
    }
}
