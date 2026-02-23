<div class="flex flex-col h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Return Barang</h1>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Kasir: {{ Auth::user()->name }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4">
        @if(!$selectedTransaction)
        <!-- Transaction Search -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cari Transaksi</h2>
                <div class="relative">
                    <input type="text"
                        wire:model.live="searchTransaction"
                        wire:keyup.debounce.300ms="searchTransaction"
                        placeholder="Cari nomor transaksi, nama pelanggan, atau nomor telepon..."
                        class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                @if(count($foundTransactions) > 0)
                <div class="mt-4 space-y-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hasil pencarian:</p>
                    @foreach($foundTransactions as $transaction)
                    <button wire:click="selectTransaction({{ $transaction['id'] }})"
                        class="w-full text-left p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Transaksi #{{ $transaction['id'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($transaction['customer'])
                                        {{ $transaction['customer']['name'] }} - {{ $transaction['customer']['phone'] }}
                                    @else
                                        Pelanggan Umum
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($transaction['total'], 0, ',', '.') }}</p>
                                <span class="text-xs px-2 py-1 rounded-full {{ $transaction['payment_method'] === 'cash' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ ucfirst($transaction['payment_method']) }}
                                </span>
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>
                @elseif(strlen($searchTransaction) >= 2)
                <div class="mt-4 text-center py-6">
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada transaksi ditemukan</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <!-- Selected Transaction Details -->
        <div class="max-w-4xl mx-auto">
            <!-- Transaction Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Transaksi #{{ $selectedTransaction->id }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $selectedTransaction->created_at->format('d/m/Y H:i') }}
                        </p>
                        @if($selectedTransaction->customer)
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Pelanggan: <span class="font-medium">{{ $selectedTransaction->customer->name }}</span>
                            @if($selectedTransaction->customer->phone) - {{ $selectedTransaction->customer->phone }}@endif
                        </p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($selectedTransaction->total, 0, ',', '.') }}</p>
                    </div>
                </div>
                <button wire:click="resetReturn" class="mt-3 text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                    ← Cari transaksi lain
                </button>
            </div>

            <!-- Items Selection -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Pilih Item untuk Return</h3>
                
                @if(count($returnItems) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                <th class="pb-2 w-10"></th>
                                <th class="pb-2">Produk</th>
                                <th class="pb-2 text-right">Harga</th>
                                <th class="pb-2 text-center">Tersedia</th>
                                <th class="pb-2 text-center">Qty Return</th>
                                <th class="pb-2 text-center">Exchange</th>
                                <th class="pb-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returnItems as $itemId => $item)
                            <tr class="border-b dark:border-gray-700 {{ $item['selected'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                                <td class="py-3">
                                    <input type="checkbox" 
                                        wire:click="toggleItemForReturn({{ $itemId }})"
                                        @checked($item['selected'])
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $item['product_name'] }}</p>
                                    @if($item['is_exchange'] && $item['exchange_product_name'])
                                    <p class="text-xs text-green-600 dark:text-green-400">
                                        Tukar dengan: {{ $item['exchange_product_name'] }} ({{ $item['exchange_quantity'] }}x)
                                    </p>
                                    @endif
                                </td>
                                <td class="py-3 text-right text-sm text-gray-600 dark:text-gray-300">
                                    Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 text-center text-sm text-gray-600 dark:text-gray-300">
                                    {{ $item['max_quantity'] }}
                                </td>
                                <td class="py-3 text-center">
                                    @if($item['selected'])
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="updateReturnQuantity({{ $itemId }}, {{ $item['quantity'] - 1 }})"
                                            class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <input type="number" 
                                            value="{{ $item['quantity'] }}"
                                            wire:change="updateReturnQuantity({{ $itemId }}, $event.target.value)"
                                            min="1" max="{{ $item['max_quantity'] }}"
                                            class="w-14 text-center rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm py-1">
                                        <button wire:click="updateReturnQuantity({{ $itemId }}, {{ $item['quantity'] + 1 }})"
                                            class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 text-center">
                                    @if($item['selected'])
                                    <button wire:click="toggleExchange({{ $itemId }})"
                                        @class([
                                            'text-xs px-2 py-1 rounded',
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $item['is_exchange'],
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => !$item['is_exchange']
                                        ])>
                                        {{ $item['is_exchange'] ? 'Ya' : 'Tidak' }}
                                    </button>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                    @if($item['selected'])
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @if($item['selected'] && $item['is_exchange'])
                            <tr class="border-b dark:border-gray-700 bg-green-50 dark:bg-green-900/10">
                                <td colspan="7" class="py-3 px-4">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">Pilih produk tukar:</span>
                                        <input type="text"
                                            wire:model.live="productSearch"
                                            wire:change.debounce.300ms="searchExchangeProducts({{ $itemId }}, $event.target.value)"
                                            placeholder="Cari produk..."
                                            class="flex-1 px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                                    </div>
                                    @if(count($exchangeProducts) > 0 && $selectedExchangeItemId === $itemId)
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($exchangeProducts as $product)
                                        <button wire:click="setExchangeProduct({{ $itemId }}, {{ $product['id'] }}, {{ $item['quantity'] }})"
                                            class="text-left p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600 hover:border-primary-500">
                                            <p class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ $product['name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Stok: {{ $product['stock'] }}</p>
                                            <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">Rp {{ number_format($product['selling_price'], 0, ',', '.') }}</p>
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada item yang bisa di-return</p>
                </div>
                @endif
            </div>

            <!-- Return Details -->
            @if(collect($returnItems)->where('selected', true)->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Left Column -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Detail Return</h3>
                    
                    <!-- Return Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipe Return</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(\App\Enums\ReturnType::cases() as $type)
                            <button wire:click="$set('returnType', '{{ $type->value }}')"
                                @class([
                                    'px-3 py-2 text-sm rounded-lg border-2 transition-colors',
                                    'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' => $returnType === $type->value,
                                    'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-gray-300' => $returnType !== $type->value
                                ])>
                                {{ $type->getLabel() }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Reason Category -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Return</label>
                        <select wire:model.live="reasonCategory"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Pilih alasan...</option>
                            @foreach(\App\Enums\ReturnReason::cases() as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reason Note -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan Alasan (Opsional)</label>
                        <textarea wire:model="reasonNote"
                            rows="2"
                            placeholder="Tambahkan detail alasan..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>

                    <!-- Refund Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metode Refund</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(\App\Enums\RefundMethod::cases() as $method)
                            @if($method->value !== 'store_credit' || $selectedTransaction->customer)
                            <button wire:click="$set('refundMethod', '{{ $method->value }}')"
                                @class([
                                    'px-3 py-2 text-sm rounded-lg border-2 transition-colors',
                                    'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' => $refundMethod === $method->value,
                                    'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-gray-300' => $refundMethod !== $method->value
                                ])>
                                {{ $method->getLabel() }}
                            </button>
                            @endif
                            @endforeach
                        </div>
                        @if($refundMethod === 'store_credit' && !$selectedTransaction->customer)
                        <p class="text-xs text-red-500 mt-1">Store credit memerlukan pelanggan</p>
                        @endif
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan Tambahan (Opsional)</label>
                        <textarea wire:model="notes"
                            rows="2"
                            placeholder="Catatan internal..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Ringkasan</h3>
                    
                    @php $totals = $this->calculateReturnTotal(); @endphp

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Item Return</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ collect($returnItems)->where('selected', true)->sum('quantity') }} item
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Nilai Return</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($totals['total_refund'], 0, ',', '.') }}
                            </span>
                        </div>

                        @if($totals['total_exchange_value'] > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Nilai Tukar</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($totals['total_exchange_value'], 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="font-medium {{ $totals['selisih_amount'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ $totals['selisih_amount'] > 0 ? 'Perlu Dibayar' : 'Perlu Direfund' }}
                                </span>
                                <span class="font-bold {{ $totals['selisih_amount'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    Rp {{ number_format(abs($totals['selisih_amount']), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t dark:border-gray-700">
                        <button wire:click="processReturn"
                            wire:loading.attr="disabled"
                            wire:target="processReturn"
                            @disabled(!$this->canProcess)
                            @class([
                                'w-full py-3 rounded-lg font-medium transition-colors',
                                'bg-primary-600 text-white hover:bg-primary-700' => $this->canProcess,
                                'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' => !$this->canProcess
                            ])>
                            <span wire:loading.remove wire:target="processReturn">Proses Return</span>
                            <span wire:loading wire:target="processReturn">Memproses...</span>
                        </button>

                        @if(!$this->canProcess && collect($returnItems)->where('selected', true)->count() > 0)
                        <p class="text-xs text-center text-red-500 mt-2">
                            @if(empty($returnType)) Pilih tipe return
                            @elseif(empty($reasonCategory)) Pilih alasan return
                            @elseif(empty($refundMethod)) Pilih metode refund
                            @elseif($returnType === 'exchange' && collect($returnItems)->where('selected', true)->where('is_exchange', true)->isEmpty()) Pilih produk tukar untuk exchange
                            @else Lengkapi semua data
                            @endif
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Success Modal -->
    @if($showSuccessModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm mx-4 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Return Berhasil!</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Return telah berhasil diproses.
                </p>

                <div class="space-y-3">
                    @if($lastReturnId)
                    <button type="button" onclick="window.printReturnReceipt({{ $lastReturnId }})"
                        class="w-full flex justify-center items-center gap-2 px-4 py-2 border border-primary-600 text-sm font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-transparent dark:hover:bg-primary-900/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Struk Return
                    </button>
                    @endif
                    <button wire:click="closeModal"
                        class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
