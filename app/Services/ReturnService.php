<?php

namespace App\Services;

use App\Enums\RefundMethod;
use App\Enums\ReturnType;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductReturnItem;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        protected StockService $stockService,
        protected StoreCreditService $storeCreditService
    ) {}

    public function generateReturnNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'RTN-'.$date;

        $lastReturn = ProductReturn::where('return_number', 'like', $prefix.'-%')
            ->orderBy('return_number', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) substr($lastReturn->return_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix.'-'.$newNumber;
    }

    public function validateReturnEligibility(Transaction $transaction, array $items): array
    {
        $errors = [];
        $store = Store::first();

        if ($transaction->created_at->diffInDays(now()) > $store->getReturnDeadline()) {
            $errors[] = 'Transaksi sudah melebihi batas waktu return ('.$store->getReturnDeadline().' hari)';
        }

        foreach ($items as $item) {
            $transactionItem = TransactionItem::find($item['transaction_item_id'] ?? null);

            if (! $transactionItem || $transactionItem->transaction_id !== $transaction->id) {
                $errors[] = 'Item transaksi tidak valid';

                continue;
            }

            $returnableQty = $transactionItem->getReturnableQuantity();
            $requestedQty = $item['quantity'] ?? 0;

            if ($requestedQty > $returnableQty) {
                $errors[] = "Quantity return untuk item '{$transactionItem->product->name}' melebihi yang bisa di-return (tersedia: {$returnableQty})";
            }

            if ($transactionItem->product && ! $transactionItem->product->isReturnable()) {
                $errors[] = "Product '{$transactionItem->product->name}' tidak dapat di-return";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function calculateRefund(array $items): array
    {
        $totalRefund = 0;
        $totalExchangeValue = 0;
        $itemsBreakdown = [];

        foreach ($items as $item) {
            $transactionItem = TransactionItem::find($item['transaction_item_id']);
            $quantity = $item['quantity'];
            $unitPrice = $transactionItem->selling_price;
            $subtotal = $quantity * $unitPrice;

            $exchangeValue = 0;
            if (isset($item['exchange_product_id'])) {
                $exchangeProduct = Product::find($item['exchange_product_id']);
                $exchangeQty = $item['exchange_quantity'] ?? $quantity;
                $exchangeValue = $exchangeQty * $exchangeProduct->selling_price;
            }

            $totalRefund += $subtotal;
            $totalExchangeValue += $exchangeValue;

            $itemsBreakdown[] = [
                'transaction_item_id' => $item['transaction_item_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'exchange_value' => $exchangeValue,
            ];
        }

        $selisihAmount = $totalExchangeValue - $totalRefund;

        return [
            'total_refund' => $totalRefund,
            'total_exchange_value' => $totalExchangeValue,
            'selisih_amount' => $selisihAmount,
            'items_breakdown' => $itemsBreakdown,
        ];
    }

    public function handlePointsReversal(Customer $customer, int $pointsToReverse, int $returnId): void
    {
        if ($pointsToReverse <= 0) {
            return;
        }

        $customer->reversePoints(
            $pointsToReverse,
            $returnId,
            'Pengurangan poin dari return #'.$returnId
        );
    }

    public function handlePointsReturn(Customer $customer, int $pointsToReturn, int $returnId): void
    {
        if ($pointsToReturn <= 0) {
            return;
        }

        $customer->returnPoints(
            $pointsToReturn,
            $returnId,
            'Pengembalian poin dari return #'.$returnId
        );
    }

    public function createReturn(Transaction $transaction, array $data): ProductReturn
    {
        return DB::transaction(function () use ($transaction, $data) {
            $validation = $this->validateReturnEligibility($transaction, $data['items']);
            if (! $validation['valid']) {
                throw new \InvalidArgumentException(implode(', ', $validation['errors']));
            }

            $returnNumber = $this->generateReturnNumber();
            $refundCalculation = $this->calculateRefund($data['items']);

            $productReturn = ProductReturn::create([
                'return_number' => $returnNumber,
                'transaction_id' => $transaction->id,
                'customer_id' => $transaction->customer_id,
                'user_id' => Auth::id(),
                'type' => $data['type'] ?? ReturnType::Partial,
                'reason_category' => $data['reason_category'],
                'reason_note' => $data['reason_note'] ?? null,
                'refund_method' => $data['refund_method'] ?? RefundMethod::Cash,
                'total_refund' => $refundCalculation['total_refund'],
                'total_exchange_value' => $refundCalculation['total_exchange_value'],
                'selisih_amount' => $refundCalculation['selisih_amount'],
                'points_reversed' => 0,
                'points_returned' => 0,
                'return_date' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $pointsReversed = 0;
            $pointsReturned = 0;

            foreach ($data['items'] as $item) {
                $transactionItem = TransactionItem::find($item['transaction_item_id']);

                $returnItem = ProductReturnItem::create([
                    'product_return_id' => $productReturn->id,
                    'transaction_item_id' => $transactionItem->id,
                    'product_id' => $transactionItem->product_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $transactionItem->selling_price,
                    'subtotal' => $item['quantity'] * $transactionItem->selling_price,
                    'is_exchange' => isset($item['exchange_product_id']),
                    'exchange_product_id' => $item['exchange_product_id'] ?? null,
                    'exchange_quantity' => $item['exchange_quantity'] ?? null,
                    'exchange_unit_price' => isset($item['exchange_product_id'])
                        ? Product::find($item['exchange_product_id'])->selling_price
                        : null,
                    'exchange_subtotal' => isset($item['exchange_product_id'])
                        ? $item['exchange_quantity'] * Product::find($item['exchange_product_id'])->selling_price
                        : null,
                    'notes' => $item['notes'] ?? null,
                ]);

                $transactionItem->increment('quantity_returned', $item['quantity']);

                $product = $transactionItem->product;
                $this->stockService->addStock(
                    $product,
                    $item['quantity'],
                    StockMovementType::Return,
                    $productReturn,
                    "Return #{$returnNumber}"
                );

                if (isset($item['exchange_product_id'])) {
                    $exchangeProduct = Product::find($item['exchange_product_id']);
                    $exchangeQty = $item['exchange_quantity'] ?? $item['quantity'];
                    $this->stockService->subtractStock(
                        $exchangeProduct,
                        $exchangeQty,
                        StockMovementType::Sale,
                        $productReturn,
                        "Exchange dari Return #{$returnNumber}"
                    );
                }

                if ($transaction->points_earned > 0) {
                    $pointsPerItem = $transaction->points_earned / $transaction->items->sum('quantity');
                    $pointsReversed += (int) round($pointsPerItem * $item['quantity']);
                }

                if ($transaction->points_redeemed > 0) {
                    $pointsPerItem = $transaction->points_redeemed / $transaction->items->sum('quantity');
                    $pointsReturned += (int) round($pointsPerItem * $item['quantity']);
                }
            }

            $customer = $transaction->customer;
            if ($customer) {
                if ($pointsReversed > 0) {
                    $this->handlePointsReversal($customer, $pointsReversed, $productReturn->id);
                }

                if ($pointsReturned > 0) {
                    $this->handlePointsReturn($customer, $pointsReturned, $productReturn->id);
                }

                $productReturn->update([
                    'points_reversed' => $pointsReversed,
                    'points_returned' => $pointsReturned,
                ]);
            }

            $this->processRefund($productReturn, $productReturn->refund_method);

            return $productReturn;
        });
    }

    protected function processRefund(ProductReturn $productReturn, RefundMethod $method): void
    {
        $refundAmount = abs($productReturn->selisih_amount);

        if ($refundAmount <= 0 && ! $productReturn->isExchange()) {
            $refundAmount = $productReturn->total_refund;
        }

        if ($refundAmount <= 0) {
            return;
        }

        match ($method) {
            RefundMethod::Cash => $this->createFinancialRecord($productReturn, $refundAmount, 'Cash'),
            RefundMethod::StoreCredit => $this->createStoreCreditRefund($productReturn, $refundAmount),
            RefundMethod::OriginalPayment => $this->createFinancialRecord($productReturn, $refundAmount, 'Original Payment'),
        };
    }

    protected function createFinancialRecord(ProductReturn $productReturn, float $amount, string $method): void
    {
        FinancialRecord::create([
            'type' => 'refund',
            'amount' => $amount,
            'profit' => -$amount,
            'transaction_id' => $productReturn->transaction_id,
            'product_return_id' => $productReturn->id,
            'description' => "Refund via {$method} - Return #{$productReturn->return_number}",
            'record_date' => now(),
        ]);
    }

    protected function createStoreCreditRefund(ProductReturn $productReturn, float $amount): void
    {
        $customer = $productReturn->customer;

        if (! $customer) {
            throw new \InvalidArgumentException('Customer tidak ditemukan untuk store credit refund');
        }

        $storeCredit = $this->storeCreditService->earnCredit(
            $customer,
            $amount,
            $productReturn->id,
            'Store credit dari return #'.$productReturn->return_number
        );

        $productReturn->update(['store_credit_id' => $storeCredit->id]);

        $this->createFinancialRecord($productReturn, $amount, 'Store Credit');
    }
}
