<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PurchaseExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $purchaseOrders;

    public function __construct(Collection $purchaseOrders)
    {
        $this->purchaseOrders = $purchaseOrders;
    }

    public function collection(): Collection
    {
        return $this->purchaseOrders;
    }

    public function headings(): array
    {
        return [
            'No. Order',
            'Tanggal Order',
            'Supplier',
            'Total',
            'Status',
            'Status Pembayaran',
            'Tanggal Diterima',
        ];
    }

    public function map($po): array
    {
        return [
            $po->order_number,
            $po->order_date->format('Y-m-d'),
            $po->supplier?->name ?? '-',
            $po->total_amount,
            ucfirst($po->status->value),
            ucfirst($po->payment_status->value),
            $po->received_date?->format('Y-m-d') ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Laporan Pembelian';
    }
}
