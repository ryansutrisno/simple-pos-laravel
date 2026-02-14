<?php

namespace App\Filament\Pages\Reports;

use App\Exports\DebtExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class DebtReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string $view = 'filament.pages.reports.debt-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Laporan Hutang';

    public array $reportData = [];

    protected ReportService $reportService;

    public function boot(ReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public function mount(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $this->reportData = $this->reportService->getDebtReport();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\SupplierDebt::query()
                ->with(['supplier', 'purchaseOrder'])
                ->whereNotIn('status', ['paid']))
            ->columns([
                TextColumn::make('debt_number')
                    ->label('No. Hutang')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('debt_date')
                    ->label('Tgl Hutang')
                    ->date('d/m/Y'),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('remaining_amount')
                    ->label('Sisa Hutang')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value)),
            ])
            ->defaultSort('due_date');
    }

    public function exportExcel()
    {
        $this->generateReport();

        return Excel::download(
            new DebtExport($this->reportData['debts']),
            'laporan-hutang-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->generateReport();

        $pdf = Pdf::loadView('reports.debt-pdf', [
            'data' => $this->reportData,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'laporan-hutang-'.now()->format('Y-m-d').'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
