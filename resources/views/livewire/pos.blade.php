<div class="flex flex-col h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Point of Sale</h1>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Kasir: {{ Auth::user()->name }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        <!-- Kolom Kiri - Produk -->
        <div class="w-full md:w-3/4 p-4 overflow-y-auto">
            <!-- Tab Kategori -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-4">
                <div class="flex overflow-x-auto p-2 space-x-2 gap-1">
                    <button wire:click="filterProductsByCategory()"
                        @class([
                            'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap',
                            'bg-primary-600 text-white' => is_null($selectedCategoryId),
                            'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !is_null($selectedCategoryId),
                        ])>
                        Semua Produk
                    </button>
                    @foreach($categories as $category)
                    <button wire:click="filterProductsByCategory({{ $category->id }})"
                        @class([
                            'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap',
                            'bg-primary-600 text-white' => $selectedCategoryId === $category->id,
                            'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $selectedCategoryId !== $category->id,
                        ])>
                        {{ $category->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mb-4">
                <div class="relative">
                    <input type="text"
                        wire:model.live="searchQuery"
                        placeholder="Cari produk..."
                        class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Grid Produk -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @forelse($products as $product)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow p-2">
                    <div class="aspect-w-1 aspect-h-1 mb-2">
                        @if($product->image)
                            <img
                                src="{{ Storage::url($product->image) }}"
                                alt="{{ $product->name }}"
                                class="w-full h-32 object-cover rounded-lg"
                            >
                        @else
                            <div class="w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $product->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Stok: {{ $product->stock }}</p>
                    <div class="mt-1 flex justify-between items-center">
                        <span class="text-primary-600 dark:text-primary-400 font-medium text-sm">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        <button
                            wire:click="addToCart({{ $product->id }})"
                            @class([
                                'px-2 py-1 rounded text-xs font-medium',
                                'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600' => $product->stock > 0,
                                'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' => $product->stock <= 0,
                            ])
                            @disabled($product->stock <= 0)>
                            {{ $product->stock > 0 ? '+' : 'Habis' }}
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">Tidak ada produk tersedia</div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan - Keranjang -->
        <div class="w-full md:w-1/4 bg-white dark:bg-gray-800 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 flex flex-col h-[40vh] md:h-auto">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Keranjang Belanja</h2>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                @if(count($cart) > 0)
                    <div class="space-y-3">
                        @foreach($cart as $index => $item)
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                            <div class="flex-1 min-w-0 pr-4">
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $item['name'] }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Rp {{ number_format($item['selling_price'], 0, ',', '.') }}</p>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <button wire:click="updateQuantity({{ $index }}, 'decrease')"
                                    class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <span class="w-8 text-center text-sm">{{ $item['quantity'] }}</span>
                                <button wire:click="updateQuantity({{ $index }}, 'increase')"
                                    class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                                <!-- Tambah tombol hapus -->
                                <button wire:click="removeFromCart({{ $index }})"
                                    class="p-1 rounded-full hover:bg-red-100 transition-colors">
                                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center py-8">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Keranjang masih kosong</p>
                    </div>
                @endif
            </div>

            @if(count($cart) > 0)
            <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800 sticky bottom-0">
                <!-- Metode Pembayaran -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metode Pembayaran</label>
                    <select wire:model.live="paymentMethod" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>

                <!-- Ringkasan Pembayaran -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format(collect($cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']), 0, ',', '.') }}</span>
                    </div>

                    @if($paymentMethod === 'cash')
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uang Dibayarkan</label>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-shrink-0">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                                </div>
                                <div class="flex-1 relative">
                                    <input
                                        type="number"
                                        wire:model.live="cashAmount"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                                        placeholder="0"
                                        min="0"
                                        x-data
                                        x-on:focus="$el.value === '0' && ($el.value = '')"
                                        x-on:blur="!$el.value && ($el.value = '0')"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Kembalian -->
                        <div class="flex justify-between text-sm mt-2">
                            <span class="text-gray-600 dark:text-gray-400">Kembalian</span>
                            <span class="font-medium {{ $change < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                Rp {{ number_format($change, 0, ',', '.') }}
                            </span>
                        </div>
                    @else
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @if($paymentMethod === 'transfer')
                                    Silakan lakukan pembayaran melalui transfer bank
                                @else
                                    Silakan scan kode QRIS untuk melakukan pembayaran
                                @endif
                            </p>
                        </div>
                    @endif

                    <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">Grand Total</span>
                        <span class="text-gray-900 dark:text-white">Rp {{ number_format(collect($cart)->sum(fn($item) => $item['selling_price'] * $item['quantity']), 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Tombol Proses -->
                <button
                    wire:click="checkout"
                    @class([
                        'w-full py-2 rounded-lg font-medium transition-colors text-sm',
                        'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600' => ($paymentMethod !== 'cash' || $this->canCheckout),
                        'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' => ($paymentMethod === 'cash' && !$this->canCheckout)
                    ])
                    @disabled($paymentMethod === 'cash' && !$this->canCheckout)>
                    Proses Pembayaran
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
