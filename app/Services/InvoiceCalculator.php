<?php

namespace App\Services;

class InvoiceCalculator
{
    /**
     * Recalculate invoice totals on the server. Line totals are quantity × unit price;
     * tax is applied per line from each item's tax rate, then discount is subtracted.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, tax_amount: float, discount: float, total: float}
     */
    public static function calculate(array $items, float $discount = 0): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $normalized = [];

        foreach ($items as $item) {
            $quantity = round((float) ($item['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
            $taxRate = round((float) ($item['tax_rate'] ?? config('company.default_tax_rate', 15)), 2);
            $lineTotal = round($quantity * $unitPrice, 2);
            $lineTax = round($lineTotal * ($taxRate / 100), 2);

            $subtotal += $lineTotal;
            $taxAmount += $lineTax;

            $normalized[] = [
                'product_id' => ! empty($item['product_id']) ? $item['product_id'] : null,
                'description' => $item['description'] ?? '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'line_total' => $lineTotal,
            ];
        }

        $discount = max(0, round($discount, 2));
        $total = round(max(0, $subtotal + $taxAmount - $discount), 2);

        return [
            'items' => $normalized,
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount' => $discount,
            'total' => $total,
        ];
    }
}
