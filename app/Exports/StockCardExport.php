<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class StockCardExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $histories;

    protected array $summary;

    public function __construct(Collection $histories, array $summary)
    {
        $this->histories = $histories;
        $this->summary = $summary;
    }

    public function collection(): Collection
    {
        return $this->histories;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Waktu',
            'Tipe',
            'Kuantitas',
            'Stok Sebelum',
            'Stok Sesudah',
            'Keterangan',
        ];
    }

    public function map($history): array
    {
        return [
            $history->created_at->format('Y-m-d'),
            $history->created_at->format('H:i:s'),
            ucfirst($history->type->value),
            $history->quantity,
            $history->stock_before,
            $history->stock_after,
            $history->note ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Kartu Stok - '.$this->summary['product']->name;
    }
}
