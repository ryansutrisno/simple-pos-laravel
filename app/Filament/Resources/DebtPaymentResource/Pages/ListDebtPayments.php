<?php

namespace App\Filament\Resources\DebtPaymentResource\Pages;

use App\Filament\Resources\DebtPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDebtPayments extends ListRecords
{
    protected static string $resource = DebtPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
