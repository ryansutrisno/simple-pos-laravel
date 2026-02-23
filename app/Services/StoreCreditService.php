<?php

namespace App\Services;

use App\Enums\StoreCreditType;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreCredit;
use Illuminate\Database\Eloquent\Collection;

class StoreCreditService
{
    public function earnCredit(Customer $customer, float $amount, int $productReturnId, ?string $description = null): StoreCredit
    {
        $store = Store::first();
        $balanceAfter = $customer->getStoreCreditBalance() + $amount;

        $expiryDate = null;
        if (! $store->isStoreCreditNeverExpires()) {
            $expiryDate = now()->addDays($store->getStoreCreditExpiryDays());
        }

        $storeCredit = StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => $productReturnId,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'type' => StoreCreditType::Earn,
            'description' => $description ?? 'Store credit dari return #'.$productReturnId,
            'expiry_date' => $expiryDate,
            'is_expired' => false,
            'is_used' => false,
        ]);

        $customer->addStoreCredit($amount);

        return $storeCredit;
    }

    public function useCredit(Customer $customer, float $amount, int $transactionId, ?string $description = null): StoreCredit
    {
        if ($customer->getStoreCreditBalance() < $amount) {
            throw new \InvalidArgumentException('Saldo store credit tidak mencukupi');
        }

        $balanceAfter = $customer->getStoreCreditBalance() - $amount;

        $storeCredit = StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'type' => StoreCreditType::Use,
            'description' => $description ?? 'Penggunaan store credit untuk transaksi #'.$transactionId,
            'is_expired' => false,
            'is_used' => true,
            'used_at' => now(),
        ]);

        $customer->useStoreCredit($amount);

        return $storeCredit;
    }

    public function getBalance(Customer $customer): float
    {
        return $customer->getStoreCreditBalance();
    }

    public function checkAndExpireCredits(): int
    {
        $expiredCredits = StoreCredit::query()
            ->where('type', StoreCreditType::Earn)
            ->where('is_expired', false)
            ->where('is_used', false)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredCredits as $credit) {
            $credit->markAsExpired();
            $credit->customer->useStoreCredit($credit->amount);
            $count++;
        }

        return $count;
    }

    public function getExpiringCredits(int $days = 30): Collection
    {
        return StoreCredit::query()
            ->where('type', StoreCreditType::Earn)
            ->where('is_expired', false)
            ->where('is_used', false)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->with('customer')
            ->orderBy('expiry_date')
            ->get();
    }
}
