<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #666; }
        .summary { margin-bottom: 20px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { color: #666; font-size: 10px; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Pembelian</h1>
        <h2>Periode: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</h2>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Pembelian</div>
                <div class="value">Rp {{ number_format($data['total_amount'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Order</div>
                <div class="value">{{ $data['total_orders'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Rata-rata/Order</div>
                <div class="value">Rp {{ number_format($data['total_orders'] > 0 ? $data['total_amount'] / $data['total_orders'] : 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @if(!empty($data['by_supplier']))
        <h3>Pembelian per Supplier</h3>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th class="text-right">Jumlah Order</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_supplier'] as $item)
                    <tr>
                        <td>{{ $item['supplier']->name ?? '-' }}</td>
                        <td class="text-right">{{ $item['count'] }}</td>
                        <td class="text-right">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3 style="margin-top: 20px;">Detail Purchase Order</h3>
    <table>
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th class="text-right">Total</th>
                <th>Status</th>
                <th>Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['purchase_orders'] as $po)
                <tr>
                    <td>{{ $po->order_number }}</td>
                    <td>{{ $po->order_date->format('d/m/Y') }}</td>
                    <td>{{ $po->supplier->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($po->status->value) }}</td>
                    <td>{{ ucfirst($po->payment_status->value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
