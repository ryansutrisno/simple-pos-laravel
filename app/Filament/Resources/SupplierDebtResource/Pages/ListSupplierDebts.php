<?php

namespace App\Filament\Resources\SupplierDebtResource\Pages;

use App\Filament\Resources\SupplierDebtResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierDebts extends ListRecords
{
    protected static string $resource = SupplierDebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
