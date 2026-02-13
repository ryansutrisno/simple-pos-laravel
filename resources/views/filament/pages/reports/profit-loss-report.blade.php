<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Laporan Laba Rugi</h2>
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
                            <div class="text-gray-500 text-sm">Total Penjualan</div>
                            <div class="text-2xl font-bold text-green-600">
                                Rp {{ number_format($reportData['total_sales'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Gross Profit</div>
                            <div class="text-2xl font-bold text-blue-600">
                                Rp {{ number_format($reportData['total_profit'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Pengeluaran</div>
                            <div class="text-2xl font-bold text-red-600">
                                Rp {{ number_format($reportData['total_expenses'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Net Profit</div>
                            <div class="text-2xl font-bold {{ $reportData['net_profit'] >= 0 ? 'text-purple-600' : 'text-red-600' }}">
                                Rp {{ number_format($reportData['net_profit'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            @if(!empty($reportData['daily_breakdown']))
                <x-filament::card>
                    <h3 class="text-lg font-semibold mb-4">Breakdown Harian</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Tanggal</th>
                                    <th class="text-right py-2">Penjualan</th>
                                    <th class="text-right py-2">Profit</th>
                                    <th class="text-right py-2">Pengeluaran</th>
                                    <th class="text-right py-2">Net Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['daily_breakdown'] as $day)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $day['date'] }}</td>
                                        <td class="text-right">Rp {{ number_format($day['sales'], 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($day['profit'], 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($day['expenses'], 0, ',', '.') }}</td>
                                        <td class="text-right {{ $day['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            Rp {{ number_format($day['net_profit'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>
            @endif

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Detail Transaksi Keuangan</h3>
                {{ $this->table }}
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
