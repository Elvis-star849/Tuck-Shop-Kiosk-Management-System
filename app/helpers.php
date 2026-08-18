<?php

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $symbol = config('company.currency_symbol', '$');

        return $symbol.number_format((float) $amount, 2);
    }
}

if (! function_exists('invoice_status_label')) {
    function invoice_status_label(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'partially_paid' => 'Partially Paid',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'pending_payment' => 'Awaiting payment',
            'cancel_requested' => 'Cancel requested',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
