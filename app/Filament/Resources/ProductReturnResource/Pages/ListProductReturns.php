<?php

namespace App\Filament\Resources\ProductReturnResource\Pages;

use App\Filament\Resources\ProductReturnResource;
use Filament\Resources\Pages\ListRecords;

class ListProductReturns extends ListRecords
{
    protected static string $resource = ProductReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
