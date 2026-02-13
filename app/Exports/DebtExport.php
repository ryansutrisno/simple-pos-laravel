<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DebtExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $debts;

    public function __construct(Collection $debts)
    {
        $this->debts = $debts;
    }

    public function collection(): Collection
    {
        return $this->debts;
    }

    public function headings(): array
    {
        return [
            'No. Hutang',
            'Supplier',
            'Tanggal Hutang',
            'Jatuh Tempo',
            'Total',
            'Dibayar',
            'Sisa',
            'Status',
            'Umur (Hari)',
        ];
    }

    public function map($debt): array
    {
        $daysOverdue = $debt->isOverdue() ? now()->diffInDays($debt->due_date) : 0;

        return [
            $debt->debt_number,
            $debt->supplier?->name ?? '-',
            $debt->debt_date->format('Y-m-d'),
            $debt->due_date->format('Y-m-d'),
            $debt->total_amount,
            $debt->paid_amount,
            $debt->remaining_amount,
            ucfirst($debt->status->value),
            $debt->isOverdue() ? "Overdue {$daysOverdue} hari" : 'Belum Jatuh Tempo',
        ];
    }

    public function title(): string
    {
        return 'Laporan Hutang';
    }
}
