<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PointService;
use App\Services\ReceiptTemplateService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Pos extends Component
{
    use WithPagination;

    public $cart = [];

    public $paymentMethod = 'cash';

    public $cashReceived = 0;

    public $searchQuery = '';

    public $selectedCategoryId = null;

    public $categories;

    public $cashAmount = 0;

    public $lastTransactionId = null;

    public $showSuccessModal = false;

    public $store;

    public $availableTemplates = [];

    public $selectedTemplateId = null;

    public $selectedCustomerId = null;

    public $customerSearch = '';

    public $redeemPoints = 0;

    public $usePoints = false;

    protected PointService $pointService;

    public function boot(PointService $pointService): void
    {
        $this->pointService = $pointService;
    }

    public function mount()
    {
        $this->categories = Category::all();
        $this->store = \App\Models\Store::first();

        $templateService = new ReceiptTemplateService;
        $this->availableTemplates = $templateService->getAvailableTemplates($this->store);

        $activeTemplate = $templateService->getActiveTemplate($this->store);
        $this->selectedTemplateId = $activeTemplate?->id;
    }

    public function updatedPaymentMethod($value)
    {
        if ($value !== 'cash') {
            $this->cashAmount = 0;
        }
    }

    public function updatedSelectedCustomerId($value)
    {
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function updatedUsePoints($value)
    {
        if ($value && $this->selectedCustomer) {
            $this->redeemPoints = $this->getMaxRedeemablePoints();
        } else {
            $this->redeemPoints = 0;
        }
    }

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;
    }

    public function getCustomerPointsProperty(): int
    {
        return $this->selectedCustomer?->points ?? 0;
    }

    public function getMaxRedeemablePoints(): int
    {
        if (! $this->selectedCustomer) {
            return 0;
        }

        $subtotal = $this->getSubtotal();

        return $this->pointService->getMaxRedeemablePoints($this->selectedCustomer->points, $subtotal);
    }

    public function getSubtotal(): float
    {
        return collect($this->cart)->sum(fn ($item) => $item['selling_price'] * $item['quantity']);
    }

    public function getDiscountFromPoints(): float
    {
        if (! $this->usePoints || $this->redeemPoints <= 0) {
            return 0;
        }

        return $this->pointService->calculateRedeemValue($this->redeemPoints);
    }

    public function getGrandTotalProperty()
    {
        return $this->getSubtotal() - $this->getDiscountFromPoints();
    }

    public function getChangeProperty()
    {
        if ($this->paymentMethod !== 'cash') {
            return 0;
        }

        return max(0, (float) $this->cashAmount - $this->grandTotal);
    }

    public function getCanCheckoutProperty()
    {
        if (empty($this->cart)) {
            return false;
        }

        if ($this->paymentMethod === 'cash') {
            return (float) $this->cashAmount >= $this->grandTotal;
        }

        return true;
    }

    public function getPointsToEarnProperty(): int
    {
        if (! $this->selectedCustomer) {
            return 0;
        }

        return $this->pointService->calculateEarnedPoints($this->grandTotal);
    }

    public function updateQuantity($index, $action)
    {
        if ($action === 'increase') {
            $this->cart[$index]['quantity']++;
        } elseif ($action === 'decrease') {
            if ($this->cart[$index]['quantity'] > 1) {
                $this->cart[$index]['quantity']--;
            }
        }

        if ($this->usePoints) {
            $this->redeemPoints = min($this->redeemPoints, $this->getMaxRedeemablePoints());
        }
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);

        if ($this->usePoints) {
            $this->redeemPoints = min($this->redeemPoints, $this->getMaxRedeemablePoints());
        }
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('barcode', 'like', '%'.$this->searchQuery.'%')
                        ->orWhereHas('category', function ($q) {
                            $q->where('name', 'like', '%'.$this->searchQuery.'%');
                        });
                });
            })
            ->when($this->selectedCategoryId, function ($query) {
                $query->where('category_id', $this->selectedCategoryId);
            })
            ->where('is_active', true)
            ->paginate(12);

        $customers = collect();
        if (strlen($this->customerSearch) >= 2) {
            $customers = Customer::where('is_active', true)
                ->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->customerSearch.'%')
                        ->orWhere('phone', 'like', '%'.$this->customerSearch.'%');
                })
                ->limit(10)
                ->get();
        }

        return view('livewire.pos', [
            'products' => $products,
            'categories' => $this->categories,
            'change' => $this->change,
            'store' => $this->store,
            'availableTemplates' => $this->availableTemplates,
            'customers' => $customers,
            'selectedCustomer' => $this->selectedCustomer,
            'grandTotal' => $this->grandTotal,
            'pointsToEarn' => $this->pointsToEarn,
        ]);
    }

    public function filterProductsByCategory($categoryId = null)
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'quantity' => 1,
            'profit' => $product->selling_price - $product->purchase_price,
        ];
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->customerSearch = '';
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function removeCustomer()
    {
        $this->selectedCustomerId = null;
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function checkout()
    {
        if (! $this->canCheckout) {
            Notification::make()
                ->title('Pembayaran tidak valid')
                ->danger()
                ->send();

            return;
        }

        $subtotal = $this->getSubtotal();
        $discountFromPoints = $this->getDiscountFromPoints();
        $grandTotal = $subtotal - $discountFromPoints;
        $totalProfit = collect($this->cart)->sum(fn ($item) => $item['profit'] * $item['quantity']);

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'customer_id' => $this->selectedCustomerId,
            'total' => $grandTotal,
            'payment_method' => $this->paymentMethod,
            'cash_amount' => $this->paymentMethod === 'cash' ? $this->cashAmount : null,
            'change_amount' => $this->paymentMethod === 'cash' ? $this->change : null,
            'points_earned' => 0,
            'points_redeemed' => $this->redeemPoints,
            'discount_from_points' => $discountFromPoints,
        ]);

        foreach ($this->cart as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['selling_price'],
                'profit' => $item['profit'],
                'subtotal' => $item['selling_price'] * $item['quantity'],
            ]);

            Product::find($item['product_id'])->decrement('stock', $item['quantity']);
        }

        if ($this->selectedCustomer) {
            if ($this->redeemPoints > 0) {
                $this->pointService->redeemPoints($this->selectedCustomer, $this->redeemPoints, $transaction->id);
            }

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
            $transaction->update(['points_earned' => $pointsEarned]);
        }

        FinancialRecord::create([
            'type' => 'sales',
            'amount' => $grandTotal,
            'profit' => $totalProfit,
            'transaction_id' => $transaction->id,
            'description' => 'Penjualan produk'.($this->selectedCustomer ? ' - '.$this->selectedCustomer->name : ''),
            'record_date' => now()->toDateString(),
        ]);

        $this->lastTransactionId = $transaction->id;

        Notification::make()
            ->title('Transaksi berhasil')
            ->success()
            ->send();

        $this->dispatch('transaction-completed', [
            'transactionId' => $transaction->id,
            'templateId' => $this->selectedTemplateId,
            'transactionData' => $this->getTransactionData($transaction->id),
        ]);

        $this->reset(['cart', 'cashAmount', 'paymentMethod', 'selectedCategoryId', 'searchQuery', 'selectedCustomerId', 'redeemPoints', 'usePoints']);
        $this->showSuccessModal = true;
    }

    public function getTransactionData($transactionId)
    {
        return Transaction::with('items.product', 'customer')->find($transactionId);
    }

    public function getTemplatesProperty()
    {
        return $this->availableTemplates->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'template_data' => $template->template_data,
            ];
        });
    }
}
