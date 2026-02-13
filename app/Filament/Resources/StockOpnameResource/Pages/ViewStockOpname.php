<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Enums\OpnameStatus;
use App\Filament\Resources\StockOpnameResource;
use App\Models\StockOpname;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('complete')
                ->label('Selesaikan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (StockOpname $record): bool => $record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stock Opname')
                ->modalDescription('Stok produk akan disesuaikan sesuai data aktual. Tindakan ini tidak dapat dibatalkan.')
                ->action(function (StockOpname $record) {
                    $record->complete();
                    Notification::make()
                        ->title('Stock Opname Selesai')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (StockOpname $record): bool => $record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Batalkan Stock Opname')
                ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                ->action(function (StockOpname $record) {
                    $record->update(['status' => OpnameStatus::Cancelled]);
                    Notification::make()
                        ->title('Stock Opname Dibatalkan')
                        ->warning()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (StockOpname $record): bool => $record->isDraft()),
        ];
    }
}
