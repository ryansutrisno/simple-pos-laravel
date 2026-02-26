<?php

namespace App\Services;

use App\Models\Store;

class TaxService
{
    /**
     * Calculate tax amount from subtotal
     */
    public function calculateTax(float $subtotal, float $taxRate): float
    {
        if ($subtotal <= 0 || $taxRate <= 0) {
            return 0;
        }

        return round($subtotal * ($taxRate / 100), 2);
    }

    /**
     * Calculate total with tax
     */
    public function calculateTotalWithTax(float $subtotal, float $taxRate): float
    {
        $taxAmount = $this->calculateTax($subtotal, $taxRate);

        return round($subtotal + $taxAmount, 2);
    }

    /**
     * Get tax data for a transaction
     */
    public function getTaxData(Store $store, float $subtotal): array
    {
        if (! $store->isTaxEnabled()) {
            return [
                'enabled' => false,
                'name' => $store->getTaxName(),
                'rate' => 0,
                'subtotal_before_tax' => $subtotal,
                'tax_amount' => 0,
                'total' => $subtotal,
            ];
        }

        $taxRate = $store->getTaxRate();
        $taxAmount = $this->calculateTax($subtotal, $taxRate);
        $total = $this->calculateTotalWithTax($subtotal, $taxRate);

        return [
            'enabled' => true,
            'name' => $store->getTaxName(),
            'rate' => $taxRate,
            'subtotal_before_tax' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * Calculate tax for array of cart items
     */
    public function calculateCartTax(array $items, float $taxRate): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            return $item['selling_price'] * $item['quantity'];
        });

        $taxAmount = $this->calculateTax($subtotal, $taxRate);
        $total = $this->calculateTotalWithTax($subtotal, $taxRate);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }
}
