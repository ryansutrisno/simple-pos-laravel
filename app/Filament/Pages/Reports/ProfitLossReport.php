<?php

namespace App\Filament\Pages\Reports;

use App\Exports\ProfitLossExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

class ProfitLossReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.reports.profit-loss-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Laporan Laba Rugi';

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

        $this->reportData = $this->reportService->getProfitLossReport(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\FinancialRecord::query()
                ->whereBetween('record_date', [
                    $this->startDate,
                    $this->endDate,
                ]))
            ->columns([
                TextColumn::make('record_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'sales' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->default(0),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(30),
            ])
            ->defaultSort('record_date', 'desc');
    }

    public function exportExcel()
    {
        $this->generateReport();

        return Excel::download(
            new ProfitLossExport(
                $this->reportData['records'],
                $this->reportData
            ),
            'laporan-laba-rugi-'.$this->startDate.'-'.$this->endDate.'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->generateReport();

        $pdf = Pdf::loadView('reports.profit-loss-pdf', [
            'data' => $this->reportData,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'laporan-laba-rugi-'.$this->startDate.'-'.$this->endDate.'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
