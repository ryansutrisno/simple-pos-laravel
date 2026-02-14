<?php

namespace App\Filament\Pages\Reports;

use App\Models\EndOfDay;
use App\Services\ReportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class EndOfDayReport extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.reports.end-of-day-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Tutup Kasir';

    public ?string $date = null;

    public ?float $actualCash = null;

    public ?string $notes = null;

    public array $summary = [];

    public ?EndOfDay $existingRecord = null;

    protected ReportService $reportService;

    public function boot(ReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadSummary();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ringkasan Hari Ini')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->loadSummary())
                            ->disabled(fn () => $this->existingRecord !== null),
                    ]),
                Section::make('Input Kas Fisik')
                    ->schema([
                        TextInput::make('actualCash')
                            ->label('Uang Fisik di Kas')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->calculateDifference())
                            ->disabled(fn () => $this->existingRecord !== null),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->disabled(fn () => $this->existingRecord !== null),
                    ])
                    ->visible(fn () => ! $this->existingRecord),
            ]);
    }

    public function loadSummary(): void
    {
        $this->summary = $this->reportService->getEndOfDaySummary(Carbon::parse($this->date));
        $this->existingRecord = EndOfDay::where('date', $this->date)->first();

        if ($this->existingRecord) {
            $this->actualCash = $this->existingRecord->actual_cash;
            $this->notes = $this->existingRecord->notes;
        }
    }

    public function calculateDifference(): void
    {
        if ($this->actualCash !== null) {
            $this->summary['difference'] = $this->actualCash - $this->summary['expected_cash'];
        }
    }

    public function save(): void
    {
        if ($this->existingRecord) {
            Notification::make()
                ->title('Sudah Ditutup')
                ->body('Kasir untuk tanggal ini sudah ditutup sebelumnya.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'actualCash' => 'required|numeric|min:0',
        ]);

        $endOfDay = $this->reportService->createEndOfDay([
            'date' => $this->date,
            'actual_cash' => $this->actualCash,
            'expected_cash' => $this->summary['expected_cash'],
            'notes' => $this->notes,
        ]);

        Notification::make()
            ->title('Kasir Berhasil Ditutup')
            ->body('Laporan end of day telah disimpan.')
            ->success()
            ->send();

        $this->existingRecord = $endOfDay;
        $this->loadSummary();
    }

    public function exportPdf()
    {
        if (! $this->existingRecord) {
            Notification::make()
                ->title('Belum Ditutup')
                ->body('Tutup kasir terlebih dahulu sebelum export.')
                ->warning()
                ->send();

            return;
        }

        $pdf = Pdf::loadView('reports.end-of-day-pdf', [
            'record' => $this->existingRecord,
            'summary' => $this->summary,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'end-of-day-'.$this->date.'.pdf'
        );
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->summary,
            'existingRecord' => $this->existingRecord,
        ];
    }
}
