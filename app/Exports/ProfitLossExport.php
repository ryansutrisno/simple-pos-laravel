<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitLossExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $records;

    protected array $summary;

    public function __construct(Collection $records, array $summary)
    {
        $this->records = $records;
        $this->summary = $summary;
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Tipe',
            'Jumlah',
            'Profit',
            'Deskripsi',
        ];
    }

    public function map($record): array
    {
        return [
            $record->record_date->format('Y-m-d'),
            ucfirst($record->type),
            $record->amount,
            $record->profit ?? 0,
            $record->description ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Laporan Laba Rugi';
    }
}
