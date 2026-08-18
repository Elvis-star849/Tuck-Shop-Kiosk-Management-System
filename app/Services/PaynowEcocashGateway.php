<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GatewayTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Paynow\Payments\Paynow;
use RuntimeException;
use Throwable;

class PaynowEcocashGateway
{
    public function simulating(): bool
    {
        return config('paynow.simulate') || blank(config('paynow.id')) || blank(config('paynow.key'));
    }

    public function initiate(Invoice|Sale $payable, float $amount, string $method = 'ecocash', ?string $phone = null): GatewayTransaction
    {
        $method = $method === 'paynow' ? 'paynow' : 'ecocash';
        $phone = $method === 'ecocash' ? $this->normalizePhone((string) $phone) : null;
        $prefix = $method === 'paynow' ? 'PN' : 'ECO';
        $reference = $this->payableNumber($payable).'-'.$prefix.'-'.now()->format('YmdHis');
        $email = config('paynow.email') ?: $this->payableEmail($payable);

        $transaction = GatewayTransaction::query()->create([
            'invoice_id' => $payable instanceof Invoice ? $payable->id : null,
            'sale_id' => $payable instanceof Sale ? $payable->id : null,
            'gateway' => 'paynow',
            'method' => $method,
            'amount' => $amount,
            'phone' => $phone,
            'reference' => $reference,
            'status' => 'pending',
            'instructions' => $this->defaultInstructions($method, $phone),
        ]);

        if ($this->simulating()) {
            $transaction->update([
                'poll_url' => 'simulate://'.$transaction->id,
                'gateway_reference' => 'SIM-'.$transaction->id,
                'payload' => ['mode' => 'simulate', 'method' => $method],
            ]);

            return $transaction->fresh();
        }

        try {
            $paynow = $this->client($transaction);
            $payment = $paynow->createPayment($reference, $email);
            $payment->add($this->payableNumber($payable).' payment', $amount);

            $response = $method === 'paynow'
                ? $paynow->send($payment)
                : $paynow->sendMobile($payment, $phone, 'ecocash');

            if (! $response->success()) {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $response->errors() ?: 'Paynow request was not accepted.',
                    'payload' => $response->data(),
                ]);

                throw new RuntimeException($transaction->error_message);
            }

            $payload = $response->data();
            if ($method === 'paynow' && $response->redirectUrl()) {
                $payload['redirect_url'] = $response->redirectUrl();
            }

            $transaction->update([
                'poll_url' => $response->pollUrl() ?: null,
                'instructions' => $response->instructions() ?: $this->defaultInstructions($method, $phone),
                'payload' => $payload,
            ]);
        } catch (Throwable $exception) {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $transaction->fresh();
    }

    public function refresh(GatewayTransaction $transaction): GatewayTransaction
    {
        if (! $transaction->isPending()) {
            return $transaction;
        }

        if ($this->simulating() || str_starts_with((string) $transaction->poll_url, 'simulate://')) {
            return $this->refreshSimulated($transaction);
        }

        if (blank($transaction->poll_url)) {
            return $transaction;
        }

        $status = $this->client($transaction)->pollTransaction($transaction->poll_url);
        $paynowStatus = strtolower((string) $status->status());

        $transaction->update([
            'gateway_reference' => $status->paynowReference() ?: $transaction->gateway_reference,
            'payload' => array_merge($transaction->payload ?? [], $status->data()),
        ]);

        if (in_array($paynowStatus, ['paid', 'awaiting delivery', 'delivered'], true)) {
            $this->markPaid($transaction, $status->paynowReference());
        } elseif (in_array($paynowStatus, ['cancelled', 'canceled'], true)) {
            $transaction->update(['status' => 'cancelled']);
        } elseif (in_array($paynowStatus, ['failed', 'error'], true)) {
            $transaction->update([
                'status' => 'failed',
                'error_message' => 'Payment failed.',
            ]);
        }

        return $transaction->fresh();
    }

    public function handleWebhook(array $payload): ?GatewayTransaction
    {
        $reference = $payload['reference'] ?? null;
        if (! $reference) {
            return null;
        }

        $transaction = GatewayTransaction::query()->where('reference', $reference)->first();
        if (! $transaction) {
            return null;
        }

        return $this->refresh($transaction);
    }

    public function simulateConfirm(GatewayTransaction $transaction): GatewayTransaction
    {
        if (! $this->simulating() && ! str_starts_with((string) $transaction->poll_url, 'simulate://')) {
            throw new RuntimeException('Demo confirmation is only available in simulate mode.');
        }

        return $this->markPaid($transaction, 'SIM-'.$transaction->id);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '263') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 3);
        }

        if (strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function refreshSimulated(GatewayTransaction $transaction): GatewayTransaction
    {
        if ($transaction->method === 'paynow') {
            return $transaction;
        }

        $outcome = config('paynow.test_numbers.'.$transaction->phone);

        return match ($outcome) {
            'paid' => $this->markPaid($transaction, 'SIM-'.$transaction->id),
            'delayed' => $transaction->created_at->lt(now()->subSeconds(12))
                ? $this->markPaid($transaction, 'SIM-'.$transaction->id)
                : $transaction,
            'cancelled' => tap($transaction, fn (GatewayTransaction $tx) => $tx->update(['status' => 'cancelled'])),
            'failed' => tap($transaction, fn (GatewayTransaction $tx) => $tx->update([
                'status' => 'failed',
                'error_message' => 'Insufficient EcoCash balance (test number).',
            ])),
            default => $transaction,
        };
    }

    private function markPaid(GatewayTransaction $transaction, ?string $gatewayReference): GatewayTransaction
    {
        return DB::transaction(function () use ($transaction, $gatewayReference) {
            $transaction = GatewayTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->status === 'paid' && $transaction->payment_id) {
                return $transaction;
            }

            if ($transaction->sale_id) {
                return $this->markSalePaid($transaction, $gatewayReference);
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($transaction->invoice_id);
            $amount = min((float) $transaction->amount, $invoice->balance() ?: (float) $transaction->amount);

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => $transaction->method === 'paynow' ? 'paynow' : 'ecocash',
                'payment_reference' => $gatewayReference ?: $transaction->reference,
                'payment_date' => now()->toDateString(),
                'notes' => $this->paymentNotes($transaction),
            ]);

            $transaction->update([
                'status' => 'paid',
                'payment_id' => $payment->id,
                'gateway_reference' => $gatewayReference ?: $transaction->gateway_reference,
                'paid_at' => now(),
            ]);

            $invoice->syncAmountPaid();

            return $transaction->fresh();
        });
    }

    private function markSalePaid(GatewayTransaction $transaction, ?string $gatewayReference): GatewayTransaction
    {
        $sale = Sale::query()->lockForUpdate()->findOrFail($transaction->sale_id);
        $outstanding = $sale->balance();
        $amount = min((float) $transaction->amount, $outstanding);

        if ($amount <= 0) {
            $transaction->update([
                'status' => 'paid',
                'gateway_reference' => $gatewayReference ?: $transaction->gateway_reference,
                'paid_at' => now(),
            ]);

            return $transaction->fresh();
        }

        $payment = Payment::query()->create([
            'sale_id' => $sale->id,
            'invoice_id' => null,
            'amount' => $amount,
            'payment_method' => $transaction->method === 'paynow' ? 'paynow' : 'ecocash',
            'payment_reference' => $gatewayReference ?: $transaction->reference,
            'payment_date' => now()->toDateString(),
            'notes' => $this->paymentNotes($transaction),
        ]);

        $paid = round((float) $sale->payments()->sum('amount'), 2);
        $completed = $paid + 0.009 >= (float) $sale->total;

        $sale->update([
            'amount_paid' => $paid,
            'change_due' => 0,
            'payment_method' => $transaction->method === 'paynow' ? 'paynow' : 'ecocash',
            'status' => $completed ? 'completed' : 'pending_payment',
        ]);

        $transaction->update([
            'status' => 'paid',
            'payment_id' => $payment->id,
            'gateway_reference' => $gatewayReference ?: $transaction->gateway_reference,
            'paid_at' => now(),
        ]);

        if ($completed) {
            AuditLog::record(
                'sale.completed',
                'Paynow '.$transaction->method.' completed '.$sale->sale_number.' for '.money($sale->total),
                $sale,
            );
        }

        return $transaction->fresh();
    }

    private function payableNumber(Invoice|Sale $payable): string
    {
        return $payable instanceof Sale ? $payable->sale_number : $payable->invoice_number;
    }

    private function payableEmail(Invoice|Sale $payable): string
    {
        if ($payable instanceof Invoice) {
            return $payable->customer?->email ?: 'billing@chindeka.test';
        }

        return $payable->user?->email ?: 'billing@chindeka.test';
    }

    private function paymentNotes(GatewayTransaction $transaction): string
    {
        if ($transaction->method === 'paynow') {
            return 'Paynow checkout ('.$transaction->reference.')';
        }

        return 'EcoCash via Paynow ('.$transaction->phone.')';
    }

    private function client(GatewayTransaction $transaction): Paynow
    {
        return new Paynow(
            (string) config('paynow.id'),
            (string) config('paynow.key'),
            route('payments.ecocash.return', $transaction),
            route('payments.ecocash.webhook'),
        );
    }

    private function defaultInstructions(string $method, ?string $phone): string
    {
        if ($method === 'paynow') {
            return 'The customer will be sent to Paynow to pay with EcoCash, card, or another supported method.';
        }

        return 'Check the EcoCash PIN prompt on '.$phone.'. Enter your EcoCash PIN to approve this payment.';
    }
}
