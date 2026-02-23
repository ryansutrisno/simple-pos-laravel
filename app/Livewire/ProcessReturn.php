<?php

namespace App\Livewire;

use App\Enums\RefundMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\ReturnService;
use Filament\Notifications\Notification;
use Livewire\Component;

class ProcessReturn extends Component
{
    public string $searchTransaction = '';

    public ?int $selectedTransactionId = null;

    public array $returnItems = [];

    public string $returnType = '';

    public string $reasonCategory = '';

    public ?string $reasonNote = null;

    public string $refundMethod = '';

    public ?string $notes = null;

    public bool $showReturnModal = false;

    public ?int $lastReturnId = null;

    public bool $showSuccessModal = false;

    public array $foundTransactions = [];

    public ?Transaction $selectedTransaction = null;

    public string $productSearch = '';

    public int $selectedExchangeItemId = 0;

    public array $exchangeProducts = [];

    protected ReturnService $returnService;

    public function boot(ReturnService $returnService): void
    {
        $this->returnService = $returnService;
    }

    public function searchTransaction(): void
    {
        if (strlen($this->searchTransaction) < 2) {
            $this->foundTransactions = [];

            return;
        }

        $this->foundTransactions = Transaction::query()
            ->with(['customer', 'items.product'])
            ->where(function ($query) {
                $query->where('id', 'like', '%'.$this->searchTransaction.'%')
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', '%'.$this->searchTransaction.'%')
                            ->orWhere('phone', 'like', '%'.$this->searchTransaction.'%');
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function selectTransaction(int $id): void
    {
        $this->selectedTransactionId = $id;
        $this->selectedTransaction = Transaction::with(['items.product', 'customer'])->find($id);
        $this->searchTransaction = '';
        $this->foundTransactions = [];
        $this->returnItems = [];

        foreach ($this->selectedTransaction->items as $item) {
            if ($item->product && $item->product->isReturnable() && $item->getReturnableQuantity() > 0) {
                $this->returnItems[$item->id] = [
                    'selected' => false,
                    'transaction_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => 0,
                    'max_quantity' => $item->getReturnableQuantity(),
                    'unit_price' => $item->selling_price,
                    'subtotal' => 0,
                    'is_exchange' => false,
                    'exchange_product_id' => null,
                    'exchange_product_name' => null,
                    'exchange_quantity' => 0,
                    'exchange_unit_price' => 0,
                    'exchange_subtotal' => 0,
                ];
            }
        }
    }

    public function toggleItemForReturn(int $itemId): void
    {
        if (! isset($this->returnItems[$itemId])) {
            return;
        }

        $this->returnItems[$itemId]['selected'] = ! $this->returnItems[$itemId]['selected'];

        if ($this->returnItems[$itemId]['selected']) {
            $this->returnItems[$itemId]['quantity'] = $this->returnItems[$itemId]['max_quantity'];
            $this->returnItems[$itemId]['subtotal'] = $this->returnItems[$itemId]['quantity'] * $this->returnItems[$itemId]['unit_price'];
        } else {
            $this->returnItems[$itemId]['quantity'] = 0;
            $this->returnItems[$itemId]['subtotal'] = 0;
            $this->returnItems[$itemId]['is_exchange'] = false;
            $this->returnItems[$itemId]['exchange_product_id'] = null;
            $this->returnItems[$itemId]['exchange_product_name'] = null;
            $this->returnItems[$itemId]['exchange_quantity'] = 0;
            $this->returnItems[$itemId]['exchange_unit_price'] = 0;
            $this->returnItems[$itemId]['exchange_subtotal'] = 0;
        }
    }

    public function updateReturnQuantity(int $itemId, int $quantity): void
    {
        if (! isset($this->returnItems[$itemId])) {
            return;
        }

        $quantity = max(0, min($quantity, $this->returnItems[$itemId]['max_quantity']));
        $this->returnItems[$itemId]['quantity'] = $quantity;
        $this->returnItems[$itemId]['subtotal'] = $quantity * $this->returnItems[$itemId]['unit_price'];

        if ($this->returnItems[$itemId]['is_exchange'] && $this->returnItems[$itemId]['exchange_product_id']) {
            $this->returnItems[$itemId]['exchange_quantity'] = $quantity;
            $this->returnItems[$itemId]['exchange_subtotal'] = $quantity * $this->returnItems[$itemId]['exchange_unit_price'];
        }
    }

    public function setExchangeProduct(int $itemId, ?int $productId, int $quantity): void
    {
        if (! isset($this->returnItems[$itemId])) {
            return;
        }

        if ($productId === null) {
            $this->returnItems[$itemId]['is_exchange'] = false;
            $this->returnItems[$itemId]['exchange_product_id'] = null;
            $this->returnItems[$itemId]['exchange_product_name'] = null;
            $this->returnItems[$itemId]['exchange_quantity'] = 0;
            $this->returnItems[$itemId]['exchange_unit_price'] = 0;
            $this->returnItems[$itemId]['exchange_subtotal'] = 0;

            return;
        }

        $product = Product::find($productId);

        if (! $product || $product->stock < $quantity) {
            Notification::make()
                ->title('Stok tidak mencukupi')
                ->danger()
                ->send();

            return;
        }

        $this->returnItems[$itemId]['is_exchange'] = true;
        $this->returnItems[$itemId]['exchange_product_id'] = $productId;
        $this->returnItems[$itemId]['exchange_product_name'] = $product->name;
        $this->returnItems[$itemId]['exchange_quantity'] = $quantity;
        $this->returnItems[$itemId]['exchange_unit_price'] = $product->selling_price;
        $this->returnItems[$itemId]['exchange_subtotal'] = $quantity * $product->selling_price;
    }

    public function toggleExchange(int $itemId): void
    {
        if (! isset($this->returnItems[$itemId])) {
            return;
        }

        $this->returnItems[$itemId]['is_exchange'] = ! $this->returnItems[$itemId]['is_exchange'];

        if (! $this->returnItems[$itemId]['is_exchange']) {
            $this->returnItems[$itemId]['exchange_product_id'] = null;
            $this->returnItems[$itemId]['exchange_product_name'] = null;
            $this->returnItems[$itemId]['exchange_quantity'] = 0;
            $this->returnItems[$itemId]['exchange_unit_price'] = 0;
            $this->returnItems[$itemId]['exchange_subtotal'] = 0;
        }
    }

    public function searchExchangeProducts(int $itemId, string $search): void
    {
        $this->selectedExchangeItemId = $itemId;

        if (strlen($search) < 2) {
            $this->exchangeProducts = [];

            return;
        }

        $this->exchangeProducts = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function calculateReturnTotal(): array
    {
        $selectedItems = collect($this->returnItems)->where('selected', true);

        $totalRefund = $selectedItems->sum('subtotal');
        $totalExchangeValue = $selectedItems->sum('exchange_subtotal');
        $selisihAmount = $totalExchangeValue - $totalRefund;

        return [
            'total_refund' => $totalRefund,
            'total_exchange_value' => $totalExchangeValue,
            'selisih_amount' => $selisihAmount,
        ];
    }

    public function getCanProcessProperty(): bool
    {
        $selectedItems = collect($this->returnItems)->where('selected', true);

        if ($selectedItems->isEmpty()) {
            return false;
        }

        if (empty($this->returnType)) {
            return false;
        }

        if (empty($this->reasonCategory)) {
            return false;
        }

        if (empty($this->refundMethod)) {
            return false;
        }

        if ($this->returnType === ReturnType::Exchange->value) {
            $hasExchange = $selectedItems->where('is_exchange', true)->isNotEmpty();
            if (! $hasExchange) {
                return false;
            }
        }

        foreach ($selectedItems as $item) {
            if ($item['quantity'] <= 0) {
                return false;
            }

            if ($item['is_exchange'] && $item['exchange_product_id']) {
                $product = Product::find($item['exchange_product_id']);
                if (! $product || $product->stock < $item['exchange_quantity']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function processReturn(): void
    {
        if (! $this->canProcess) {
            Notification::make()
                ->title('Data tidak valid')
                ->danger()
                ->send();

            return;
        }

        $selectedItems = collect($this->returnItems)->where('selected', true);

        $items = $selectedItems->map(function ($item) {
            $data = [
                'transaction_item_id' => $item['transaction_item_id'],
                'quantity' => $item['quantity'],
            ];

            if ($item['is_exchange'] && $item['exchange_product_id']) {
                $data['exchange_product_id'] = $item['exchange_product_id'];
                $data['exchange_quantity'] = $item['exchange_quantity'];
            }

            return $data;
        })->toArray();

        try {
            $productReturn = $this->returnService->createReturn($this->selectedTransaction, [
                'type' => ReturnType::from($this->returnType),
                'reason_category' => ReturnReason::from($this->reasonCategory),
                'reason_note' => $this->reasonNote,
                'refund_method' => RefundMethod::from($this->refundMethod),
                'notes' => $this->notes,
                'items' => $items,
            ]);

            $this->lastReturnId = $productReturn->id;
            $this->showReturnModal = false;
            $this->showSuccessModal = true;

            $this->dispatch('return-completed', [
                'returnId' => $productReturn->id,
                'returnNumber' => $productReturn->return_number,
            ]);

            Notification::make()
                ->title('Return berhasil diproses')
                ->body('Return #'.$productReturn->return_number.' telah dibuat')
                ->success()
                ->send();

        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Gagal memproses return')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetReturn(): void
    {
        $this->searchTransaction = '';
        $this->selectedTransactionId = null;
        $this->selectedTransaction = null;
        $this->returnItems = [];
        $this->returnType = '';
        $this->reasonCategory = '';
        $this->reasonNote = null;
        $this->refundMethod = '';
        $this->notes = null;
        $this->foundTransactions = [];
        $this->exchangeProducts = [];
        $this->selectedExchangeItemId = 0;
        $this->productSearch = '';
    }

    public function closeModal(): void
    {
        $this->showSuccessModal = false;
        $this->resetReturn();
    }

    public function render()
    {
        return view('livewire.process-return');
    }
}
