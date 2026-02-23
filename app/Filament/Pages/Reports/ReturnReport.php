<?php

namespace App\Filament\Pages\Reports;

use App\Services\ReportService;
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

class ReturnReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string $view = 'filament.pages.reports.return-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Laporan Return';

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

        $this->reportData = $this->reportService->getReturnReport(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\ProductReturn::query()
                ->with(['customer', 'user'])
                ->whereBetween('return_date', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ]))
            ->columns([
                TextColumn::make('return_number')
                    ->label('No. Return')
                    ->searchable(),
                TextColumn::make('return_date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => $state->getColor()),
                TextColumn::make('reason_category')
                    ->label('Alasan')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '-')
                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),
                TextColumn::make('total_refund')
                    ->label('Total Refund')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('refund_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '-')
                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),
                TextColumn::make('user.name')
                    ->label('Diproses Oleh')
                    ->searchable(),
            ])
            ->defaultSort('return_date', 'desc');
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
