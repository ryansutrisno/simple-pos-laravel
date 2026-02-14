<?php

namespace App\Filament\Pages\Reports;

use App\Exports\StockCardExport;
use App\Models\Product;
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

class StockCardReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.pages.reports.stock-card-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Kartu Stok';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $productId = null;

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
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        Select::make('productId')
                            ->label('Produk')
                            ->options(Product::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
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
                    ->columns(3),
            ]);
    }

    public function generateReport(): void
    {
        if (! $this->productId || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $this->reportData = $this->reportService->getStockCardReport(
            $this->productId,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\StockHistory::query()
                ->where('product_id', $this->productId)
                ->when($this->startDate && $this->endDate, fn ($q) => $q->whereBetween('created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ])))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value))
                    ->color(fn ($state) => match ($state->value) {
                        'in', 'purchase' => 'success',
                        'out', 'sale' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($record) => ($record->type->value === 'in' || $record->type->value === 'purchase' ? '+' : '-').$record->quantity),
                TextColumn::make('stock_before')
                    ->label('Stok Awal'),
                TextColumn::make('stock_after')
                    ->label('Stok Akhir'),
                TextColumn::make('note')
                    ->label('Keterangan')
                    ->limit(30),
            ])
            ->defaultSort('created_at');
    }

    public function exportExcel()
    {
        $this->generateReport();

        if (empty($this->reportData)) {
            return;
        }

        return Excel::download(
            new StockCardExport($this->reportData['histories'], $this->reportData),
            'kartu-stok-'.$this->reportData['product']->name.'-'.$this->startDate.'-'.$this->endDate.'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->generateReport();

        if (empty($this->reportData)) {
            return;
        }

        $pdf = Pdf::loadView('reports.stock-card-pdf', [
            'data' => $this->reportData,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'kartu-stok-'.$this->reportData['product']->name.'-'.$this->startDate.'-'.$this->endDate.'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
