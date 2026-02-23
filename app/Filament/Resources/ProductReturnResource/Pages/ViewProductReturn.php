<?php

namespace App\Filament\Resources\ProductReturnResource\Pages;

use App\Filament\Resources\ProductReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductReturn extends ViewRecord
{
    protected static string $resource = ProductReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak Struk')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => '#')
                ->extraAttributes(fn () => [
                    'onclick' => "window.printReturnReceipt({$this->record->id}); return false;",
                ]),
            Actions\DeleteAction::make()
                ->label('Hapus'),
        ];
    }
}
