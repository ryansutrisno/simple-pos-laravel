<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Tutup Kasir (End of Day)</h2>
            @if($existingRecord)
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-document-text"
                    wire:click="exportPdf"
                >
                    Export PDF
                </x-filament::button>
            @endif
        </div>

        {{ $this->form }}

        @if(!empty($summary))
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Penjualan</div>
                            <div class="text-2xl font-bold text-green-600">
                                Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-500">{{ $summary['total_transactions'] }} transaksi</div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Profit</div>
                            <div class="text-2xl font-bold text-blue-600">
                                Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Saldo Awal</div>
                            <div class="text-2xl font-bold text-gray-600">
                                Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Expected Cash</div>
                            <div class="text-2xl font-bold text-purple-600">
                                Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-500">Saldo awal + penjualan cash</div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Breakdown per Metode Pembayaran</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-green-700 font-semibold">Cash</div>
                        <div class="text-xl font-bold text-green-700">
                            Rp {{ number_format($summary['total_cash_sales'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-500">{{ $summary['cash_transactions'] }} transaksi</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-blue-700 font-semibold">Transfer</div>
                        <div class="text-xl font-bold text-blue-700">
                            Rp {{ number_format($summary['total_transfer_sales'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-500">{{ $summary['transfer_transactions'] }} transaksi</div>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-purple-700 font-semibold">QRIS</div>
                        <div class="text-xl font-bold text-purple-700">
                            Rp {{ number_format($summary['total_qris_sales'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-500">{{ $summary['qris_transactions'] }} transaksi</div>
                    </div>
                </div>
            </x-filament::card>

            @if(!$existingRecord)
                <x-filament::card>
                    <h3 class="text-lg font-semibold mb-4">Hitung Selisih</h3>
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-gray-600 text-sm">Expected Cash</div>
                            <div class="text-xl font-bold">
                                Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl">-</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-gray-600 text-sm">Actual Cash (Input)</div>
                            <div class="text-xl font-bold {{ $actualCash ? '' : 'text-gray-400' }}">
                                Rp {{ number_format($actualCash ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    @if($actualCash !== null)
                        <div class="mt-4 text-center p-4 {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg">
                            <div class="{{ ($actualCash - $summary['expected_cash']) >= 0 ? 'text-green-700' : 'text-red-700' }} text-sm">
                                {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'SELISIH LEBIH' : 'SELISIH KURANG' }}
                            </div>
                            <div class="text-2xl font-bold {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                Rp {{ number_format(abs($actualCash - $summary['expected_cash']), 0, ',', '.') }}
                            </div>
                        </div>
                    @endif
                </x-filament::card>

                <x-filament::card>
                    <x-filament::button
                        color="success"
                        size="lg"
                        wire:click="save"
                        class="w-full"
                    >
                        Simpan & Tutup Kasir
                    </x-filament::button>
                </x-filament::card>
            @else
                <x-filament::card>
                    <div class="text-center p-6 bg-green-50 rounded-lg">
                        <div class="text-green-700 text-lg font-semibold">Kasir Sudah Ditutup</div>
                        <div class="text-gray-600 mt-2">
                            Ditutup oleh: {{ $existingRecord->user->name }} pada {{ $existingRecord->updated_at->format('d/m/Y H:i') }}
                        </div>
                        @if($existingRecord->notes)
                            <div class="text-gray-500 mt-2">Catatan: {{ $existingRecord->notes }}</div>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-gray-600 text-sm">Expected Cash</div>
                            <div class="text-xl font-bold">
                                Rp {{ number_format($existingRecord->expected_cash, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-gray-600 text-sm">Actual Cash</div>
                            <div class="text-xl font-bold">
                                Rp {{ number_format($existingRecord->actual_cash, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="text-center p-4 {{ $existingRecord->difference >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg">
                            <div class="{{ $existingRecord->difference >= 0 ? 'text-green-700' : 'text-red-700' }} text-sm">
                                {{ $existingRecord->difference >= 0 ? 'Selisih Lebih' : 'Selisih Kurang' }}
                            </div>
                            <div class="text-xl font-bold {{ $existingRecord->difference >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                Rp {{ number_format(abs($existingRecord->difference), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </x-filament::card>
            @endif
        @endif
    </div>
</x-filament-panels::page>
