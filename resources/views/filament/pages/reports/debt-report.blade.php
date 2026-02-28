<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Laporan Hutang</h2>
            <div class="flex gap-2">
                <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="exportExcel"> Export
                    Excel </x-filament::button>
                <x-filament::button color="danger" icon="heroicon-o-document-text" wire:click="exportPdf"> Export PDF
                </x-filament::button>
            </div>
        </div> @if(!empty($reportData)) <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-gray-500 text-sm">Total Hutang</div>
                        <div class="text-2xl font-bold text-red-600"> Rp {{ number_format($reportData['total_debt'], 0,
                            ',', '.') }} </div>
                        <div class="text-sm invisible">-</div>
                    </div>
                </x-filament::card>
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-gray-500 text-sm">Sudah Dibayar</div>
                        <div class="text-2xl font-bold text-green-600"> Rp {{ number_format($reportData['total_paid'],
                            0, ',', '.') }} </div>
                        <div class="text-sm invisible">-</div>
                    </div>
                </x-filament::card>
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-gray-500 text-sm">Overdue</div>
                        <div class="text-2xl font-bold text-orange-600"> Rp {{
                            number_format($reportData['total_overdue'], 0, ',', '.') }} </div>
                        <div class="text-sm text-gray-500">{{ $reportData['overdue_count'] }} hutang</div>
                    </div>
                </x-filament::card>
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-filament::card>
                    <div class="text-center">
                        <div class="text-gray-500 text-sm">Total Hutang Aktif</div>
                        <div class="text-2xl font-bold text-blue-600"> {{ $reportData['debts']->count() }} </div>
                        <div class="text-sm invisible">-</div>
                    </div>
                </x-filament::card>
            </div>
        </div> @if(!empty($reportData['aging_report'])) <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">Aging Report</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4"> @foreach($reportData['aging_report'] as $category =>
                $data) <div
                    class="text-center p-4 {{ $data['category'] === 'Overdue' ? 'bg-red-50' : 'bg-green-50' }} rounded-lg">
                    <div
                        class="font-semibold {{ $data['category'] === 'Overdue' ? 'text-red-700' : 'text-green-700' }}">
                        {{ $category }} </div>
                    <div class="text-xl font-bold mt-2"> Rp {{ number_format($data['total'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500">{{ $data['count'] }} hutang</div>
                </div> @endforeach </div>
        </x-filament::card> @endif @if(!empty($reportData['by_supplier'])) <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">Hutang per Supplier</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Supplier</th>
                            <th class="text-right py-2">Total Hutang</th>
                            <th class="text-right py-2">Dibayar</th>
                            <th class="text-right py-2">Sisa</th>
                        </tr>
                    </thead>
                    <tbody> @foreach($reportData['by_supplier'] as $item) <tr class="border-b">
                            <td class="py-2">{{ $item['supplier']->name ?? '-' }}</td>
                            <td class="text-right">Rp {{ number_format($item['total_debt'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item['total_paid'], 0, ',', '.') }}</td>
                            <td class="text-right font-semibold text-red-600">Rp {{
                                number_format($item['total_remaining'], 0, ',', '.') }}</td>
                        </tr> @endforeach </tbody>
                </table>
            </div>
        </x-filament::card> @endif <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">Detail Hutang</h3> {{ $this->table }}
        </x-filament::card> @endif
    </div>
</x-filament-panels::page>