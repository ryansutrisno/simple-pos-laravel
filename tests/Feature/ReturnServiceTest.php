<?php

use App\Enums\RefundMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Enums\StoreCreditType;
use App\Models\Customer;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Store;
use App\Models\StoreCredit;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\ReturnService;
use App\Services\StoreCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    Store::create([
        'name' => 'Test Store',
        'address' => 'Test Address',
        'phone' => '08123456789',
        'return_deadline_days' => 7,
        'enable_store_credit' => true,
        'store_credit_expiry_days' => 180,
        'store_credit_never_expires' => false,
    ]);

    $this->returnService = app(ReturnService::class);
    $this->storeCreditService = app(StoreCreditService::class);
});

describe('ReturnService - generateReturnNumber', function () {
    it('generates correct format with date and sequence', function () {
        $returnNumber = $this->returnService->generateReturnNumber();

        expect($returnNumber)->toMatch('/^RTN-\d{8}-\d{4}$/');
    });

    it('starts with 0001 for first return of the day', function () {
        $returnNumber = $this->returnService->generateReturnNumber();

        expect($returnNumber)->toEndWith('-0001');
    });

    it('increments sequence for subsequent returns', function () {
        $firstNumber = $this->returnService->generateReturnNumber();

        $transaction = Transaction::factory()->create();
        ProductReturn::create([
            'return_number' => $firstNumber,
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 100000,
            'total_exchange_value' => 0,
            'selisih_amount' => -100000,
            'return_date' => now(),
        ]);

        $secondNumber = $this->returnService->generateReturnNumber();

        expect($secondNumber)->toEndWith('-0002');
    });

    it('resets sequence on different day', function () {
        $yesterday = now()->subDay()->format('Ymd');
        $transaction = Transaction::factory()->create();

        ProductReturn::create([
            'return_number' => "RTN-{$yesterday}-0005",
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 100000,
            'total_exchange_value' => 0,
            'selisih_amount' => -100000,
            'return_date' => now()->subDay(),
        ]);

        $todayNumber = $this->returnService->generateReturnNumber();

        expect($todayNumber)->toEndWith('-0001');
    });
});

describe('ReturnService - validateReturnEligibility', function () {
    it('validates successfully for eligible transaction', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(3),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->validateReturnEligibility($transaction, [
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
        ]);

        expect($result['valid'])->toBeTrue()
            ->and($result['errors'])->toBeEmpty();
    });

    it('fails when transaction exceeds deadline', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->validateReturnEligibility($transaction, [
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
        ]);

        expect($result['valid'])->toBeFalse()
            ->and($result['errors'])->toHaveCount(1)
            ->and($result['errors'][0])->toContain('melebihi batas waktu');
    });

    it('fails when quantity exceeds returnable amount', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(3),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'quantity_returned' => 1,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 3,
            'profit' => ($product->selling_price - $product->purchase_price) * 3,
        ]);

        $result = $this->returnService->validateReturnEligibility($transaction, [
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 5],
        ]);

        expect($result['valid'])->toBeFalse()
            ->and($result['errors'][0])->toContain('melebihi yang bisa di-return');
    });

    it('fails when product is not returnable', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => false,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(3),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->validateReturnEligibility($transaction, [
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
        ]);

        expect($result['valid'])->toBeFalse()
            ->and($result['errors'][0])->toContain('tidak dapat di-return');
    });

    it('fails when transaction item does not belong to transaction', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(3),
        ]);

        $otherTransaction = Transaction::factory()->create();
        $transactionItem = TransactionItem::create([
            'transaction_id' => $otherTransaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->validateReturnEligibility($transaction, [
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
        ]);

        expect($result['valid'])->toBeFalse()
            ->and($result['errors'][0])->toContain('Item transaksi tidak valid');
    });
});

describe('ReturnService - calculateRefund', function () {
    it('calculates refund correctly for simple return', function () {
        $product = Product::factory()->create([
            'selling_price' => 50000,
            'purchase_price' => 30000,
        ]);

        $transaction = Transaction::factory()->create();
        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->calculateRefund([
            ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
        ]);

        expect($result['total_refund'])->toEqual(100000)
            ->and($result['total_exchange_value'])->toEqual(0)
            ->and($result['selisih_amount'])->toEqual(-100000)
            ->and($result['items_breakdown'])->toHaveCount(1);
    });

    it('calculates refund correctly with exchange product', function () {
        $product = Product::factory()->create([
            'selling_price' => 50000,
            'purchase_price' => 30000,
        ]);

        $exchangeProduct = Product::factory()->create([
            'selling_price' => 75000,
            'purchase_price' => 50000,
        ]);

        $transaction = Transaction::factory()->create();
        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $result = $this->returnService->calculateRefund([
            [
                'transaction_item_id' => $transactionItem->id,
                'quantity' => 2,
                'exchange_product_id' => $exchangeProduct->id,
                'exchange_quantity' => 2,
            ],
        ]);

        expect($result['total_refund'])->toEqual(100000)
            ->and($result['total_exchange_value'])->toEqual(150000)
            ->and($result['selisih_amount'])->toEqual(50000);
    });

    it('calculates refund correctly with multiple items', function () {
        $product1 = Product::factory()->create(['selling_price' => 30000]);
        $product2 = Product::factory()->create(['selling_price' => 50000]);

        $transaction = Transaction::factory()->create();
        $transactionItem1 = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product1->id,
            'quantity' => 3,
            'selling_price' => $product1->selling_price,
            'purchase_price' => $product1->purchase_price,
            'subtotal' => $product1->selling_price * 3,
            'profit' => ($product1->selling_price - $product1->purchase_price) * 3,
        ]);
        $transactionItem2 = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'selling_price' => $product2->selling_price,
            'purchase_price' => $product2->purchase_price,
            'subtotal' => $product2->selling_price * 2,
            'profit' => ($product2->selling_price - $product2->purchase_price) * 2,
        ]);

        $result = $this->returnService->calculateRefund([
            ['transaction_item_id' => $transactionItem1->id, 'quantity' => 2],
            ['transaction_item_id' => $transactionItem2->id, 'quantity' => 1],
        ]);

        expect($result['total_refund'])->toEqual(110000)
            ->and($result['items_breakdown'])->toHaveCount(2);
    });
});

describe('ReturnService - createReturn', function () {
    it('creates return with items successfully', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $productReturn = $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]);

        expect($productReturn)->toBeInstanceOf(ProductReturn::class)
            ->and($productReturn->return_number)->toMatch('/^RTN-\d{8}-\d{4}$/')
            ->and($productReturn->items)->toHaveCount(1)
            ->and((float) $productReturn->total_refund)->toEqual($product->selling_price * 2);
    });

    it('updates stock after return', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 3],
            ],
        ]);

        $product->refresh();
        expect($product->stock)->toBe(13);
    });

    it('updates quantity_returned on transaction item', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 3],
            ],
        ]);

        $transactionItem->refresh();
        expect($transactionItem->quantity_returned)->toBe(3);
    });

    it('handles exchange by subtracting exchange product stock', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);
        $exchangeProduct = Product::factory()->create(['stock' => 20]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Exchange,
            'reason_category' => ReturnReason::WrongItem,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                [
                    'transaction_item_id' => $transactionItem->id,
                    'quantity' => 2,
                    'exchange_product_id' => $exchangeProduct->id,
                    'exchange_quantity' => 2,
                ],
            ],
        ]);

        $product->refresh();
        $exchangeProduct->refresh();

        expect($product->stock)->toBe(12)
            ->and($exchangeProduct->stock)->toBe(18);
    });

    it('throws exception for invalid return', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => false,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        expect(fn () => $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]))->toThrow(\InvalidArgumentException::class);
    });
});

describe('ReturnService - Points Handling', function () {
    it('reverses earned points correctly', function () {
        $customer = Customer::factory()->create(['points' => 100]);
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(2),
            'points_earned' => 50,
            'points_redeemed' => 0,
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $productReturn = $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]);

        $customer->refresh();
        expect($productReturn->points_reversed)->toBeGreaterThan(0)
            ->and($customer->points)->toBeLessThan(100);
    });

    it('returns redeemed points correctly', function () {
        $customer = Customer::factory()->create(['points' => 100]);
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(2),
            'points_earned' => 0,
            'points_redeemed' => 30,
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $productReturn = $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]);

        $customer->refresh();
        expect($productReturn->points_returned)->toBeGreaterThan(0)
            ->and($customer->points)->toBeGreaterThan(100);
    });

    it('handles points reversal with zero points', function () {
        $customer = Customer::factory()->create(['points' => 50]);

        $this->returnService->handlePointsReversal($customer, 0, 1);

        $customer->refresh();
        expect($customer->points)->toBe(50);
    });

    it('handles points return with zero points', function () {
        $customer = Customer::factory()->create(['points' => 50]);

        $this->returnService->handlePointsReturn($customer, 0, 1);

        $customer->refresh();
        expect($customer->points)->toBe(50);
    });
});

describe('ReturnService - Refund Processing', function () {
    it('creates financial record for cash refund', function () {
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
            'selling_price' => 50000,
        ]);

        $transaction = Transaction::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $productReturn = $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]);

        $financialRecord = FinancialRecord::where('product_return_id', $productReturn->id)->first();

        expect($financialRecord)->not->toBeNull()
            ->and($financialRecord->type)->toBe('refund')
            ->and((float) $financialRecord->amount)->toEqual(100000);
    });

    it('creates store credit for store credit refund', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 0]);
        $product = Product::factory()->create([
            'stock' => 10,
            'is_returnable' => true,
            'selling_price' => 50000,
        ]);

        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(2),
        ]);

        $transactionItem = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_returned' => 0,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'subtotal' => $product->selling_price * 5,
            'profit' => ($product->selling_price - $product->purchase_price) * 5,
        ]);

        $productReturn = $this->returnService->createReturn($transaction, [
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::StoreCredit,
            'items' => [
                ['transaction_item_id' => $transactionItem->id, 'quantity' => 2],
            ],
        ]);

        $storeCredit = StoreCredit::where('product_return_id', $productReturn->id)->first();
        $customer->refresh();

        expect($storeCredit)->not->toBeNull()
            ->and($storeCredit->type)->toBe(StoreCreditType::Earn)
            ->and((float) $storeCredit->amount)->toEqual(100000)
            ->and((float) $customer->store_credit_balance)->toEqual(100000);
    });
});

describe('StoreCreditService - earnCredit', function () {
    it('creates store credit with correct values', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 0]);

        $transaction = Transaction::factory()->create();
        $productReturn = ProductReturn::create([
            'return_number' => 'RTN-20260223-0001',
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 50000,
            'total_exchange_value' => 0,
            'selisih_amount' => -50000,
            'return_date' => now(),
        ]);

        $storeCredit = $this->storeCreditService->earnCredit(
            $customer,
            50000,
            $productReturn->id,
            'Test credit'
        );

        expect($storeCredit)->toBeInstanceOf(StoreCredit::class)
            ->and((float) $storeCredit->amount)->toEqual(50000)
            ->and($storeCredit->type)->toBe(StoreCreditType::Earn)
            ->and($storeCredit->description)->toBe('Test credit')
            ->and($storeCredit->is_expired)->toBeFalse()
            ->and($storeCredit->is_used)->toBeFalse();
    });

    it('sets expiry date based on store settings', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 0]);

        $transaction = Transaction::factory()->create();
        $productReturn = ProductReturn::create([
            'return_number' => 'RTN-20260223-0002',
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 50000,
            'total_exchange_value' => 0,
            'selisih_amount' => -50000,
            'return_date' => now(),
        ]);

        $storeCredit = $this->storeCreditService->earnCredit(
            $customer,
            50000,
            $productReturn->id
        );

        expect($storeCredit->expiry_date)->not->toBeNull()
            ->and($storeCredit->expiry_date->diffInDays(now()))->toBeLessThanOrEqual(181);
    });

    it('does not set expiry date when store credit never expires', function () {
        Store::first()->update(['store_credit_never_expires' => true]);
        $customer = Customer::factory()->create(['store_credit_balance' => 0]);

        $transaction = Transaction::factory()->create();
        $productReturn = ProductReturn::create([
            'return_number' => 'RTN-20260223-0003',
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 50000,
            'total_exchange_value' => 0,
            'selisih_amount' => -50000,
            'return_date' => now(),
        ]);

        $storeCredit = $this->storeCreditService->earnCredit(
            $customer,
            50000,
            $productReturn->id
        );

        expect($storeCredit->expiry_date)->toBeNull();
    });

    it('updates customer store credit balance', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 25000]);

        $transaction = Transaction::factory()->create();
        $productReturn = ProductReturn::create([
            'return_number' => 'RTN-20260223-0004',
            'transaction_id' => $transaction->id,
            'user_id' => $this->user->id,
            'type' => ReturnType::Partial,
            'reason_category' => ReturnReason::Damaged,
            'refund_method' => RefundMethod::Cash,
            'total_refund' => 50000,
            'total_exchange_value' => 0,
            'selisih_amount' => -50000,
            'return_date' => now(),
        ]);

        $this->storeCreditService->earnCredit(
            $customer,
            50000,
            $productReturn->id
        );

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(75000);
    });
});

describe('StoreCreditService - useCredit', function () {
    it('creates usage record correctly', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        $storeCredit = $this->storeCreditService->useCredit(
            $customer,
            50000,
            1
        );

        expect($storeCredit)->toBeInstanceOf(StoreCredit::class)
            ->and((float) $storeCredit->amount)->toEqual(50000)
            ->and($storeCredit->type)->toBe(StoreCreditType::Use)
            ->and($storeCredit->is_used)->toBeTrue()
            ->and($storeCredit->used_at)->not->toBeNull();
    });

    it('updates customer balance after usage', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        $this->storeCreditService->useCredit(
            $customer,
            50000,
            1
        );

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(50000);
    });

    it('throws exception when balance insufficient', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 10000]);

        expect(fn () => $this->storeCreditService->useCredit(
            $customer,
            50000,
            1
        ))->toThrow(\InvalidArgumentException::class, 'Saldo store credit tidak mencukupi');
    });

    it('allows using exact balance', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 50000]);

        $storeCredit = $this->storeCreditService->useCredit(
            $customer,
            50000,
            1
        );

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(0)
            ->and((float) $storeCredit->balance_after)->toEqual(0);
    });
});

describe('StoreCreditService - getBalance', function () {
    it('returns correct balance', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 75000.50]);

        $balance = $this->storeCreditService->getBalance($customer);

        expect($balance)->toEqual(75000.50);
    });

    it('returns zero for customer with no credit', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 0]);

        $balance = $this->storeCreditService->getBalance($customer);

        expect($balance)->toEqual(0);
    });
});

describe('StoreCreditService - checkAndExpireCredits', function () {
    it('expires credits past expiry date', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => now()->subDay(),
            'is_expired' => false,
            'is_used' => false,
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(1);

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(50000);
    });

    it('does not expire credits with future expiry date', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => now()->addDays(30),
            'is_expired' => false,
            'is_used' => false,
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(0);

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(100000);
    });

    it('does not expire already expired credits', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => now()->subDay(),
            'is_expired' => true,
            'is_used' => false,
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(0);
    });

    it('does not expire used credits', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => now()->subDay(),
            'is_expired' => false,
            'is_used' => true,
            'used_at' => now()->subDays(5),
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(0);
    });

    it('does not expire credits with null expiry date', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => null,
            'is_expired' => false,
            'is_used' => false,
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(0);
    });

    it('expires multiple credits at once', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 150000]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit 1',
            'expiry_date' => now()->subDay(),
            'is_expired' => false,
            'is_used' => false,
        ]);

        StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 30000,
            'balance_after' => 30000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit 2',
            'expiry_date' => now()->subDays(2),
            'is_expired' => false,
            'is_used' => false,
        ]);

        $count = $this->storeCreditService->checkAndExpireCredits();

        expect($count)->toBe(2);

        $customer->refresh();
        expect((float) $customer->store_credit_balance)->toEqual(70000);
    });

    it('marks expired credits with correct timestamp', function () {
        $customer = Customer::factory()->create(['store_credit_balance' => 100000]);

        $storeCredit = StoreCredit::create([
            'customer_id' => $customer->id,
            'product_return_id' => null,
            'amount' => 50000,
            'balance_after' => 50000,
            'type' => StoreCreditType::Earn,
            'description' => 'Test credit',
            'expiry_date' => now()->subDay(),
            'is_expired' => false,
            'is_used' => false,
        ]);

        $this->storeCreditService->checkAndExpireCredits();

        $storeCredit->refresh();
        expect($storeCredit->is_expired)->toBeTrue()
            ->and($storeCredit->expired_at)->not->toBeNull();
    });
});
