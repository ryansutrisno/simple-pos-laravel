<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $transactions;

    protected string $startDate;

    protected string $endDate;

    public function __construct(Collection $transactions, string $startDate, string $endDate)
    {
        $this->transactions = $transactions;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'No. Transaksi',
            'Tanggal',
            'Waktu',
            'Metode Pembayaran',
            'Total',
            'Profit',
            'Item',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            $transaction->created_at->format('Y-m-d'),
            $transaction->created_at->format('H:i:s'),
            ucfirst($transaction->payment_method),
            $transaction->total,
            $transaction->items->sum('profit'),
            $transaction->items->count(),
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }
}
