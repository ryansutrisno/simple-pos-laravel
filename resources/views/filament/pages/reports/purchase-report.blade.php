<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Laporan Pembelian</h2>
            <div class="flex gap-2">
                <x-filament::button
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="exportExcel"
                >
                    Export Excel
                </x-filament::button>
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-document-text"
                    wire:click="exportPdf"
                >
                    Export PDF
                </x-filament::button>
            </div>
        </div>

        {{ $this->form }}

        @if(!empty($reportData))
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Pembelian</div>
                            <div class="text-2xl font-bold text-red-600">
                                Rp {{ number_format($reportData['total_amount'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Order</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $reportData['total_orders'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Rata-rata/Order</div>
                            <div class="text-2xl font-bold text-orange-600">
                                Rp {{ number_format($reportData['total_orders'] > 0 ? $reportData['total_amount'] / $reportData['total_orders'] : 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            @if(!empty($reportData['by_supplier']))
                <x-filament::card>
                    <h3 class="text-lg font-semibold mb-4">Pembelian per Supplier</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Supplier</th>
                                    <th class="text-right py-2">Jumlah Order</th>
                                    <th class="text-right py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['by_supplier'] as $item)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $item['supplier']->name ?? '-' }}</td>
                                        <td class="text-right">{{ $item['count'] }}</td>
                                        <td class="text-right">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>
            @endif

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Detail Purchase Order</h3>
                {{ $this->table }}
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
