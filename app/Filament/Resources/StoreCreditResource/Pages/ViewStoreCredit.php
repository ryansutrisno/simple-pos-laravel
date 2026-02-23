<?php

namespace App\Filament\Resources\StoreCreditResource\Pages;

use App\Filament\Resources\StoreCreditResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreCredit extends ViewRecord
{
    protected static string $resource = StoreCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
