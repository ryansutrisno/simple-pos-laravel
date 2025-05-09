<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
use App\Models\FinancialRecord;
use App\Models\Transaction;
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

    public function mount()
    {
        $this->categories = Category::all();
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
        // Konversi cashAmount ke float untuk memastikan tipe data yang benar
        return max(0, (float) $this->cashAmount - $total);
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->searchQuery, function ($query) { // Changed from $search to $searchQuery
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
            'change' => $this->change
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

        $this->cart = [];
        $this->cashAmount = 0;

        $this->dispatch('transaction-completed');

        Notification::make()
            ->title('Transaksi Berhasil')
            ->success()
            ->send();
    }

    public function updateQuantity($index, $action)
    {
        if ($action === 'increase') {
            $this->cart[$index]['quantity']++;
        } elseif ($action === 'decrease' && $this->cart[$index]['quantity'] > 1) {
            $this->cart[$index]['quantity']--;
        }
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // Re-index array
    }

    public function getCanCheckoutProperty()
    {
        if (empty($this->cart)) {
            return false;
        }

        if ($this->paymentMethod === 'cash') {
            $total = collect($this->cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']);
            return $this->cashAmount >= $total;
        }

        return true; // Untuk metode pembayaran non-tunai
    }
}
