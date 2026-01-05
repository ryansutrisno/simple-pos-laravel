<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
use App\Models\FinancialRecord;
use App\Models\Transaction;
use App\Models\ReceiptTemplate;
use App\Services\ReceiptTemplateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Filament\Notifications\Notification;

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

    public function mount()
    {
        $this->categories = Category::all();
        $this->store = \App\Models\Store::first();
        
        // Load available receipt templates
        $templateService = new ReceiptTemplateService();
        $this->availableTemplates = $templateService->getAvailableTemplates($this->store);
        
        // Set the active template or default
        $activeTemplate = $templateService->getActiveTemplate($this->store);
        $this->selectedTemplateId = $activeTemplate?->id;
    }

    public function updatedPaymentMethod($value)
    {
        if ($value !== 'cash') {
            $this->cashAmount = 0;
        }
    }

    public function getChangeProperty()
    {
        if ($this->paymentMethod !== 'cash') {
            return 0;
        }

        $total = collect($this->cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']);
        return max(0, (float) $this->cashAmount - $total);
    }

    public function getCanCheckoutProperty()
    {
        if (empty($this->cart)) {
            return false;
        }

        if ($this->paymentMethod === 'cash') {
            $total = collect($this->cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']);
            return (float) $this->cashAmount >= $total;
        }

        return true;
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
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
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
                    $q->where('name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('barcode', 'like', '%' . $this->searchQuery . '%')
                        ->orWhereHas('category', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchQuery . '%');
                        });
                });
            })
            ->when($this->selectedCategoryId, function ($query) {
                $query->where('category_id', $this->selectedCategoryId);
            })
            ->where('is_active', true)
            ->paginate(12);

        return view('livewire.pos', [
            'products' => $products,
            'categories' => $this->categories,
            'change' => $this->change,
            'store' => $this->store,
            'availableTemplates' => $this->availableTemplates,
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
            'profit' => $product->selling_price - $product->purchase_price
        ];
    }

    public function checkout()
    {
        if (!$this->canCheckout) {
            Notification::make()
                ->title('Pembayaran tidak valid')
                ->danger()
                ->send();
            return;
        }

        $total = collect($this->cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']);
        $totalProfit = collect($this->cart)->sum(fn($item) => $item['profit'] * $item['quantity']);

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'total' => $total,
            'payment_method' => $this->paymentMethod,
            'cash_amount' => $this->paymentMethod === 'cash' ? $this->cashAmount : null,
            'change_amount' => $this->paymentMethod === 'cash' ? $this->change : null,
        ]);

        foreach ($this->cart as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['selling_price'],
                'profit' => $item['profit'],
                'subtotal' => $item['selling_price'] * $item['quantity']
            ]);

            Product::find($item['product_id'])->decrement('stock', $item['quantity']);
        }

        // Buat catatan keuangan
        FinancialRecord::create([
            'type' => 'sales',
            'amount' => $total,
            'profit' => $totalProfit,
            'transaction_id' => $transaction->id,
            'description' => 'Penjualan produk',
            'record_date' => now()->toDateString()
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

        $this->reset(['cart', 'cashAmount', 'paymentMethod', 'selectedCategoryId', 'searchQuery']);
        $this->showSuccessModal = true;
    }

    public function getTransactionData($transactionId)
    {
        return Transaction::with('items.product')->find($transactionId);
    }

    /**
     * Get available templates for the frontend
     */
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
