<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .product-info { margin-bottom: 15px; padding: 10px; background: #f5f5f5; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { color: #666; font-size: 10px; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .in { color: green; }
        .out { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kartu Stok</h1>
        <p>Periode: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</p>
    </div>

    <div class="product-info">
        <strong>Produk:</strong> {{ $data['product']->name }}
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Stok Awal</div>
            <div class="value">{{ $data['opening_stock'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Masuk</div>
            <div class="value in">+{{ $data['total_in'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Keluar</div>
            <div class="value out">-{{ $data['total_out'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Stok Akhir</div>
            <div class="value">{{ $data['closing_stock'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Stok Awal</th>
                <th class="text-center">Stok Akhir</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['histories'] as $history)
                <tr>
                    <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($history->type->value) }}</td>
                    <td class="text-center {{ $history->isIn() ? 'in' : 'out' }}">
                        {{ $history->isIn() ? '+' : '-' }}{{ $history->quantity }}
                    </td>
                    <td class="text-center">{{ $history->stock_before }}</td>
                    <td class="text-center">{{ $history->stock_after }}</td>
                    <td>{{ $history->note ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
