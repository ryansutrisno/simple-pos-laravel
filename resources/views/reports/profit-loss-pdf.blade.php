<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
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
        .profit { color: green; }
        .loss { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Laba Rugi</h1>
        <h2>Periode: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</h2>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Penjualan</div>
                <div class="value">Rp {{ number_format($data['total_sales'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Gross Profit</div>
                <div class="value profit">Rp {{ number_format($data['total_profit'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Pengeluaran</div>
                <div class="value loss">Rp {{ number_format($data['total_expenses'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Net Profit</div>
                <div class="value {{ $data['net_profit'] >= 0 ? 'profit' : 'loss' }}">
                    Rp {{ number_format($data['net_profit'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    @if(!empty($data['daily_breakdown']))
        <h3>Breakdown Harian</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-right">Profit</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_breakdown'] as $day)
                    <tr>
                        <td>{{ $day['date'] }}</td>
                        <td class="text-right">Rp {{ number_format($day['sales'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($day['profit'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($day['expenses'], 0, ',', '.') }}</td>
                        <td class="text-right {{ $day['net_profit'] >= 0 ? 'profit' : 'loss' }}">
                            Rp {{ number_format($day['net_profit'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
