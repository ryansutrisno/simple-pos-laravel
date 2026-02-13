<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Kartu Stok</h2>
            @if(!empty($reportData))
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
            @endif
        </div>

        {{ $this->form }}

        @if(!empty($reportData))
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[150px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Produk</div>
                            <div class="text-lg font-bold">{{ $reportData['product']->name }}</div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Stok Awal</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $reportData['opening_stock'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Masuk</div>
                            <div class="text-2xl font-bold text-green-600">
                                +{{ $reportData['total_in'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Keluar</div>
                            <div class="text-2xl font-bold text-red-600">
                                -{{ $reportData['total_out'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Stok Akhir</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ $reportData['closing_stock'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Riwayat Pergerakan Stok</h3>
                {{ $this->table }}
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
