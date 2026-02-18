<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Collection;

class DiscountService
{
    public function getProductDiscounts(Product $product): Collection
    {
        return $product->activeDiscounts()->get();
    }

    public function getCategoryDiscounts(Product $product): Collection
    {
        return $product->category?->discounts()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get() ?? collect();
    }

    public function getGlobalDiscounts(): Collection
    {
        return Discount::where('target_type', 'global')
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    public function validateVoucher(string $code, float $subtotal = 0): ?Discount
    {
        $discount = Discount::where('code', strtoupper($code))
            ->where('target_type', 'voucher')
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->first();

        if (! $discount) {
            return null;
        }

        if ($discount->hasReachedLimit()) {
            return null;
        }

        if ($discount->min_purchase && $subtotal < $discount->min_purchase) {
            return null;
        }

        return $discount;
    }

    public function calculateProductDiscount(Product $product, int $quantity = 1): array
    {
        $originalPrice = $product->selling_price;
        $productDiscounts = $this->getProductDiscounts($product);
        $categoryDiscounts = $this->getCategoryDiscounts($product);

        $allDiscounts = $productDiscounts->merge($categoryDiscounts);

        if ($allDiscounts->isEmpty()) {
            return [
                'original_price' => $originalPrice,
                'discount_amount' => 0,
                'final_price' => $originalPrice,
                'discount' => null,
            ];
        }

        $bestDiscount = $allDiscounts->sortByDesc(function ($discount) use ($originalPrice) {
            return $discount->calculateDiscount($originalPrice);
        })->first();

        $discountAmount = $bestDiscount->calculateDiscount($originalPrice);
        $finalPrice = max(0, $originalPrice - $discountAmount);

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'discount' => $bestDiscount,
        ];
    }

    public function calculateTransactionDiscount(float $subtotal, ?string $voucherCode = null): array
    {
        $result = [
            'subtotal' => $subtotal,
            'global_discount_amount' => 0,
            'voucher_discount_amount' => 0,
            'total_discount' => 0,
            'final_total' => $subtotal,
            'global_discount' => null,
            'voucher_discount' => null,
        ];

        $globalDiscounts = $this->getGlobalDiscounts();
        if ($globalDiscounts->isNotEmpty()) {
            $bestGlobal = $globalDiscounts->sortByDesc(function ($discount) use ($subtotal) {
                return $discount->calculateDiscount($subtotal);
            })->first();

            $result['global_discount_amount'] = $bestGlobal->calculateDiscount($subtotal);
            $result['global_discount'] = $bestGlobal;
        }

        if ($voucherCode) {
            $subtotalAfterGlobal = $subtotal - $result['global_discount_amount'];
            $voucher = $this->validateVoucher($voucherCode, $subtotalAfterGlobal);

            if ($voucher) {
                $result['voucher_discount_amount'] = $voucher->calculateDiscount($subtotalAfterGlobal);
                $result['voucher_discount'] = $voucher;
            }
        }

        $result['total_discount'] = $result['global_discount_amount'] + $result['voucher_discount_amount'];
        $result['final_total'] = max(0, $subtotal - $result['total_discount']);

        return $result;
    }

    public function incrementUsage(Discount $discount): void
    {
        $discount->increment('used_count');
    }
}
