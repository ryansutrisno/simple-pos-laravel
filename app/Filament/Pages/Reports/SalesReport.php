<?php

namespace App\Filament\Pages\Reports;

use App\Exports\SalesExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class SalesReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.reports.sales-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Penjualan';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $reportData = [];

    protected ReportService $reportService;

    public function boot(ReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(2),
            ]);
    }

    public function generateReport(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->reportData = $this->reportService->getSalesReport(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\Transaction::query()
                ->with(['items.product'])
                ->whereBetween('created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ]))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('items_sum_profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->getStateUsing(fn ($record) => $record->items->sum('profit')),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->getStateUsing(fn ($record) => $record->items->count()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function exportExcel()
    {
        $this->generateReport();

        return Excel::download(
            new SalesExport(
                $this->reportData['transactions'],
                $this->startDate,
                $this->endDate
            ),
            'laporan-penjualan-'.$this->startDate.'-'.$this->endDate.'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->generateReport();

        $pdf = Pdf::loadView('reports.sales-pdf', [
            'data' => $this->reportData,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'laporan-penjualan-'.$this->startDate.'-'.$this->endDate.'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
