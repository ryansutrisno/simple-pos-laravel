<?php

namespace App\Filament\Pages\Reports;

use App\Exports\PurchaseExport;
use App\Models\Supplier;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string $view = 'filament.pages.reports.purchase-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Laporan Pembelian';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $supplierId = null;

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
                        Select::make('supplierId')
                            ->label('Supplier')
                            ->options(Supplier::pluck('name', 'id'))
                            ->placeholder('Semua Supplier')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(3),
            ]);
    }

    public function generateReport(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->reportData = $this->reportService->getPurchaseReport(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->supplierId
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\PurchaseOrder::query()
                ->with(['supplier', 'items'])
                ->whereBetween('order_date', [
                    $this->startDate,
                    $this->endDate,
                ])
                ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId)))
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Order')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value)),
            ])
            ->defaultSort('order_date', 'desc');
    }

    public function exportExcel()
    {
        $this->generateReport();

        return Excel::download(
            new PurchaseExport($this->reportData['purchase_orders']),
            'laporan-pembelian-'.$this->startDate.'-'.$this->endDate.'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->generateReport();

        $pdf = Pdf::loadView('reports.purchase-pdf', [
            'data' => $this->reportData,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'laporan-pembelian-'.$this->startDate.'-'.$this->endDate.'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
