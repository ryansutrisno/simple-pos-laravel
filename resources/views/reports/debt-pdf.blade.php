<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Hutang</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { color: #666; font-size: 10px; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .overdue { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Hutang Supplier</h1>
        <p>Tanggal: {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Hutang</div>
            <div class="value">Rp {{ number_format($data['total_debt'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Sudah Dibayar</div>
            <div class="value">Rp {{ number_format($data['total_paid'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Overdue</div>
            <div class="value overdue">Rp {{ number_format($data['total_overdue'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Hutang Aktif</div>
            <div class="value">{{ $data['debts']->count() }}</div>
        </div>
    </div>

    @if(!empty($data['by_supplier']))
        <h3>Hutang per Supplier</h3>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th class="text-right">Total Hutang</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_supplier'] as $item)
                    <tr>
                        <td>{{ $item['supplier']->name ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($item['total_debt'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item['total_paid'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item['total_remaining'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3 style="margin-top: 20px;">Detail Hutang</h3>
    <table>
        <thead>
            <tr>
                <th>No. Hutang</th>
                <th>Supplier</th>
                <th>Tgl Hutang</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Sisa</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['debts'] as $debt)
                <tr class="{{ $debt->isOverdue() ? 'overdue' : '' }}">
                    <td>{{ $debt->debt_number }}</td>
                    <td>{{ $debt->supplier->name ?? '-' }}</td>
                    <td>{{ $debt->debt_date->format('d/m/Y') }}</td>
                    <td>{{ $debt->due_date->format('d/m/Y') }}</td>
                    <td class="text-right">Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($debt->status->value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
