<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>End of Day Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #666; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { color: #666; font-size: 10px; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        .payment-breakdown { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .payment-item { padding: 15px; text-align: center; border-radius: 5px; }
        .cash { background: #dcfce7; }
        .transfer { background: #dbeafe; }
        .qris { background: #f3e8ff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .profit { color: green; }
        .loss { color: red; }
        .signature-section { margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
        .signature-box { text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 60px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Tutup Kasir</h1>
        <h2>Tanggal: {{ $record->date->format('d/m/Y') }}</h2>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Penjualan</div>
            <div class="value">Rp {{ number_format($record->total_sales, 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ $record->total_transactions }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Profit</div>
            <div class="value profit">Rp {{ number_format($record->total_profit, 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Saldo Awal</div>
            <div class="value">Rp {{ number_format($record->opening_balance, 0, ',', '.') }}</div>
        </div>
    </div>

    <h3>Breakdown Metode Pembayaran</h3>
    <div class="payment-breakdown">
        <div class="payment-item cash">
            <div class="label">Cash</div>
            <div class="value">Rp {{ number_format($record->total_cash_sales, 0, ',', '.') }}</div>
        </div>
        <div class="payment-item transfer">
            <div class="label">Transfer</div>
            <div class="value">Rp {{ number_format($record->total_transfer_sales, 0, ',', '.') }}</div>
        </div>
        <div class="payment-item qris">
            <div class="label">QRIS</div>
            <div class="value">Rp {{ number_format($record->total_qris_sales, 0, ',', '.') }}</div>
        </div>
    </div>

    <h3>Perhitungan Kas</h3>
    <table>
        <tr>
            <th>Keterangan</th>
            <th class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Saldo Awal Kas</td>
            <td class="text-right">Rp {{ number_format($record->opening_balance, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Penjualan Cash</td>
            <td class="text-right">Rp {{ number_format($record->total_cash_sales, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Expected Cash (Seharusnya)</th>
            <th class="text-right">Rp {{ number_format($record->expected_cash, 0, ',', '.') }}</th>
        </tr>
        <tr>
            <td>Actual Cash (Fisik)</td>
            <td class="text-right">Rp {{ number_format($record->actual_cash, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Selisih</th>
            <th class="text-right {{ $record->difference >= 0 ? 'profit' : 'loss' }}">
                {{ $record->difference >= 0 ? '+' : '' }}Rp {{ number_format($record->difference, 0, ',', '.') }}
            </th>
        </tr>
    </table>

    @if($record->notes)
        <div style="margin-top: 20px;">
            <strong>Catatan:</strong><br>
            {{ $record->notes }}
        </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                Kasir<br>
                {{ $record->user->name }}
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Manager<br>
                _______________
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 10px;">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
