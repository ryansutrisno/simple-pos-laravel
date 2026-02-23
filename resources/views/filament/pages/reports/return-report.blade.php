<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Return</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reportData['total_returns'] ?? 0 }}</p>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Refund</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">Rp {{ number_format($reportData['total_refund'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Exchange</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($reportData['total_exchange_value'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Poin Terlibat</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        <span class="text-red-500">-{{ $reportData['points_reversed'] ?? 0 }}</span> /
                        <span class="text-green-500">+{{ $reportData['points_returned'] ?? 0 }}</span>
                    </p>
                </div>
            </x-filament::card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(!empty($reportData['by_type']))
            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Berdasarkan Tipe</h3>
                <div class="space-y-2">
                    @foreach($reportData['by_type'] as $item)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                        <span class="text-sm font-medium dark:text-white">{{ $item['count'] }} (Rp {{ number_format($item['total'], 0, ',', '.') }})</span>
                    </div>
                    @endforeach
                </div>
            </x-filament::card>
            @endif

            @if(!empty($reportData['by_reason']))
            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Berdasarkan Alasan</h3>
                <div class="space-y-2">
                    @foreach($reportData['by_reason'] as $item)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                        <span class="text-sm font-medium dark:text-white">{{ $item['count'] }} (Rp {{ number_format($item['total'], 0, ',', '.') }})</span>
                    </div>
                    @endforeach
                </div>
            </x-filament::card>
            @endif

            @if(!empty($reportData['by_refund_method']))
            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Berdasarkan Metode Refund</h3>
                <div class="space-y-2">
                    @foreach($reportData['by_refund_method'] as $item)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                        <span class="text-sm font-medium dark:text-white">{{ $item['count'] }} (Rp {{ number_format($item['total'], 0, ',', '.') }})</span>
                    </div>
                    @endforeach
                </div>
            </x-filament::card>
            @endif
        </div>

        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4 dark:text-white">Detail Transaksi Return</h3>
            {{ $this->table }}
        </x-filament::card>
    </div>
</x-filament-panels::page>
