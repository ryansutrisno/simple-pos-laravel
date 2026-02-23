<?php

namespace App\Filament\Resources;

use App\Enums\StoreCreditType;
use App\Filament\Resources\StoreCreditResource\Pages;
use App\Models\StoreCredit;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreCreditResource extends Resource
{
    protected static ?string $model = StoreCredit::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Store Credit';

    protected static ?string $pluralLabel = 'Store Credit';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($infolist->record)
            ->schema([
                Section::make('Informasi Credit')
                    ->schema([
                        TextEntry::make('customer.name')
                            ->label('Pelanggan'),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge()
                            ->color(fn (StoreCreditType $state): string => $state->getColor())
                            ->formatStateUsing(fn (StoreCreditType $state): string => $state->getLabel()),
                        TextEntry::make('amount')
                            ->label('Jumlah')
                            ->money('IDR'),
                        TextEntry::make('balance_after')
                            ->label('Saldo Akhir')
                            ->money('IDR'),
                        TextEntry::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Referensi')
                    ->schema([
                        TextEntry::make('productReturn.id')
                            ->label('Retur Produk')
                            ->default('-')
                            ->url(fn ($record) => $record->product_return_id ? route('filament.admin.resources.product-returns.view', $record->product_return_id) : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('Status')
                    ->schema([
                        IconEntry::make('is_expired')
                            ->label('Kedaluwarsa')
                            ->boolean(),
                        TextEntry::make('expired_at')
                            ->label('Tanggal Kedaluwarsa')
                            ->dateTime('d M Y H:i')
                            ->default('-'),
                        IconEntry::make('is_used')
                            ->label('Digunakan')
                            ->boolean(),
                        TextEntry::make('used_at')
                            ->label('Tanggal Digunakan')
                            ->dateTime('d M Y H:i')
                            ->default('-'),
                        TextEntry::make('expiry_date')
                            ->label('Tanggal Kadaluarsa')
                            ->date('d M Y')
                            ->default('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (StoreCreditType $state): string => $state->getColor())
                    ->formatStateUsing(fn (StoreCreditType $state): string => $state->getLabel()),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->color(fn (StoreCredit $record): string => $record->type === StoreCreditType::Earn ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Kadaluarsa')
                    ->date('d M Y')
                    ->color(function (StoreCredit $record): ?string {
                        if (! $record->expiry_date || $record->is_expired || $record->is_used) {
                            return null;
                        }

                        if ($record->expiry_date->isPast()) {
                            return 'danger';
                        }

                        if ($record->expiry_date->diffInDays(now()) <= 30) {
                            return 'warning';
                        }

                        return null;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->html()
                    ->formatStateUsing(function (StoreCredit $record): string {
                        $badges = [];

                        if ($record->is_used) {
                            $badges[] = '<span class="fi-badge flex items-center justify-center gap-x-1 rounded-md bg-info-100 px-2 py-0.5 text-xs font-medium text-info-700 dark:bg-info-500/20 dark:text-info-300">Digunakan</span>';
                        }

                        if ($record->is_expired) {
                            $badges[] = '<span class="fi-badge flex items-center justify-center gap-x-1 rounded-md bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-500/20 dark:text-danger-300">Kedaluwarsa</span>';
                        }

                        return implode(' ', $badges) ?: '-';
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(collect(StoreCreditType::cases())->mapWithKeys(fn (StoreCreditType $type) => [$type->value => $type->getLabel()])),
                TernaryFilter::make('is_expired')
                    ->label('Kedaluwarsa'),
                TernaryFilter::make('is_used')
                    ->label('Digunakan'),
                SelectFilter::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Pelanggan'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreCredits::route('/'),
            'view' => Pages\ViewStoreCredit::route('/{record}'),
        ];
    }
}
