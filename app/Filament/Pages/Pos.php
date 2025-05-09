<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Pos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'POS';
    protected static ?string $title = 'Point of Sale';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.pos';
}
