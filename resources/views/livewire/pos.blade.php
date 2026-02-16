<div class="flex flex-col h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Point of Sale</h1>
                    <span id="printer-status" class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-300">
                        <svg id="printer-icon-status" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-500">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                        </svg>
                        <span id="printer-status-text">Printer tidak terhubung</span>
                    </span>
                </div>

                <div class="flex items-center gap-3 mt-2 md:mt-0">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Kasir: {{ Auth::user()->name }}
                    </div>
                    <button onclick="window.connectPrinter()"
                        class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M7.5 6v4.5a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V6a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75ZM7.5 15v4.5a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V15a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75Z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M4.5 3a2.25 2.25 0 0 0-2.25 2.25v11.25c0 .896.432 1.69 1.098 2.188l1.9-4.188c.237-.521.754-.874 1.328-.874h7.5a1.5 1.5 0 0 0 1.328-.874l1.9-4.188A2.252 2.252 0 0 0 19.5 5.25V5.25a2.25 2.25 0 0 0-2.25-2.25H4.5ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" clip-rule="evenodd" />
                        </svg>
                        Hubungkan Printer
                    </button>
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
                        @class([ 'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap' , 'bg-primary-600 text-white'=> is_null($selectedCategoryId),
                        'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !is_null($selectedCategoryId),
                        ])>
                        Semua Produk
                    </button>
                    @foreach($categories as $category)
                    <button wire:click="filterProductsByCategory({{ $category->id }})"
                        @class([ 'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap' , 'bg-primary-600 text-white'=> $selectedCategoryId === $category->id,
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
                            class="w-full h-32 object-cover rounded-lg">
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
                            @class([ 'px-2 py-1 rounded text-xs font-medium' , 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600'=> $product->stock > 0,
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
                <!-- Customer Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pelanggan (Opsional)</label>
                    @if($selectedCustomer)
                    <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div>
                            <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $selectedCustomer->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $selectedCustomer->phone }} | Poin: {{ $selectedCustomer->points }}
                            </p>
                        </div>
                        <button wire:click="removeCustomer" class="text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @else
                    <div class="relative">
                        <input type="text"
                            wire:model.live="customerSearch"
                            placeholder="Cari nama atau no. telepon..."
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        @if(strlen($customerSearch) >= 2 && $customers->count() > 0)
                        <div class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                            @foreach($customers as $customer)
                            <button wire:click="selectCustomer({{ $customer->id }})" class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $customer->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs block">{{ $customer->phone }} - Poin: {{ $customer->points }}</span>
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Points Redemption -->
                @if($selectedCustomer && $selectedCustomer->points >= 10)
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="usePoints" id="usePoints" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <label for="usePoints" class="text-sm font-medium text-gray-700 dark:text-gray-300">Gunakan Poin</label>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Saldo: {{ $selectedCustomer->points }}</span>
                    </div>
                    @if($usePoints)
                    <div class="mt-2">
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live="redeemPoints" min="0" max="{{ $getMaxRedeemablePoints() }}" class="flex-1 px-2 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                            <button wire:click="$set('redeemPoints', {{ $getMaxRedeemablePoints() }})" class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 rounded">Max</button>
                        </div>
                        @if($redeemPoints > 0)
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            Diskon: Rp {{ number_format($redeemPoints * 1000, 0, ',', '.') }}
                        </p>
                        @endif
                    </div>
                    @endif
                </div>
                @endif

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
                        <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($getSubtotal(), 0, ',', '.') }}</span>
                    </div>

                    @if($usePoints && $redeemPoints > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-yellow-600 dark:text-yellow-400">Diskon Poin (-{{ $redeemPoints }} poin)</span>
                        <span class="font-medium text-yellow-600 dark:text-yellow-400">- Rp {{ number_format($redeemPoints * 1000, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    @if($selectedCustomer && $pointsToEarn > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-green-600 dark:text-green-400">Poin yang didapat</span>
                        <span class="font-medium text-green-600 dark:text-green-400">+{{ $pointsToEarn }} poin</span>
                    </div>
                    @endif

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
                                    x-on:blur="!$el.value && ($el.value = '0')">
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
                        <span class="text-gray-900 dark:text-white">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Tombol Proses -->
                <button
                    wire:click="checkout"
                    wire:loading.attr="disabled"
                    wire:target="checkout"
                    @class([ 'w-full py-2 rounded-lg font-medium transition-colors text-sm' , 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600'=> ($paymentMethod !== 'cash' || $this->canCheckout),
                    'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' => ($paymentMethod === 'cash' && !$this->canCheckout)
                    ])
                    @disabled($paymentMethod === 'cash' && !$this->canCheckout)>
                    <span wire:loading.remove wire:target="checkout">Proses Pembayaran</span>
                    <span wire:loading wire:target="checkout">Memproses...</span>
                </button>

                @endif

                {{-- Success Modal --}}
                @if($showSuccessModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm mx-4 shadow-xl transform transition-all">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Transaksi Berhasil!</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Transaksi telah berhasil disimpan.</p>

                            <div class="space-y-3">
                                <button type="button" onclick="window.printReceipt('{{ $lastTransactionId }}')"
                                    class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    Cetak Struk
                                </button>

                                <button wire:click="$set('showSuccessModal', false)"
                                    class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- JavaScript untuk Bluetooth Printer -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.bluetoothPrinter) {
                    window.bluetoothPrinter.autoConnect();
                }
            });

            document.addEventListener('livewire:init', () => {
                Livewire.on('transaction-completed', (data) => {
                    if (window.bluetoothPrinter && window.bluetoothPrinter.connected) {
                        window.printReceipt(data.transactionId);
                    }
                });
            });
        </script>
    </div>
