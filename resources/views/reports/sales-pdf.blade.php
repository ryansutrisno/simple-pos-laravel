<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #666; }
        .summary { margin-bottom: 20px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { color: #666; font-size: 10px; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Penjualan</h1>
        <h2>Periode: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</h2>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Penjualan</div>
                <div class="value">Rp {{ number_format($data['total_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Transaksi</div>
                <div class="value">{{ $data['total_transactions'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Profit</div>
                <div class="value">Rp {{ number_format($data['total_profit'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Rata-rata/Transaksi</div>
                <div class="value">Rp {{ number_format($data['total_transactions'] > 0 ? $data['total_sales'] / $data['total_transactions'] : 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Metode</th>
                <th class="text-right">Total</th>
                <th class="text-right">Profit</th>
                <th class="text-center">Item</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($data['transactions'] as $transaction)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($transaction->payment_method) }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->items->sum('profit'), 0, ',', '.') }}</td>
                    <td class="text-center">{{ $transaction->items->count() }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total</th>
                <th class="text-right">Rp {{ number_format($data['total_sales'], 0, ',', '.') }}</th>
                <th class="text-right">Rp {{ number_format($data['total_profit'], 0, ',', '.') }}</th>
                <th class="text-center">{{ $data['total_transactions'] }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
