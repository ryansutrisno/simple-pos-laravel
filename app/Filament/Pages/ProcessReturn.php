<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class ProcessReturn extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Return Barang';

    protected static ?string $title = 'Proses Return';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.process-return';
}
