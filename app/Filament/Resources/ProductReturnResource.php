<?php

namespace App\Filament\Resources;

use App\Enums\RefundMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Filament\Resources\ProductReturnResource\Pages;
use App\Models\ProductReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductReturnResource extends Resource
{
    protected static ?string $model = ProductReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Return Barang';

    protected static ?string $pluralLabel = 'Return Barang';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Return')
                            ->schema([
                                Forms\Components\TextInput::make('return_number')
                                    ->label('Nomor Return')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('return_date')
                                    ->label('Tanggal Return')
                                    ->disabled(),
                                Forms\Components\Placeholder::make('type')
                                    ->label('Tipe Return')
                                    ->content(fn ($record): string => $record?->type?->getLabel() ?? '-'),
                                Forms\Components\Placeholder::make('reason_category')
                                    ->label('Kategori Alasan')
                                    ->content(fn ($record): string => $record?->reason_category?->getLabel() ?? '-'),
                                Forms\Components\Textarea::make('reason_note')
                                    ->label('Catatan Alasan')
                                    ->disabled()
                                    ->rows(2),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Transaksi Asli')
                            ->schema([
                                Forms\Components\Placeholder::make('transaction_number')
                                    ->label('Nomor Transaksi')
                                    ->content(function ($record) {
                                        if (! $record?->transaction) {
                                            return '-';
                                        }

                                        return $record->transaction->transaction_number ?? '-';
                                    }),
                                Forms\Components\Placeholder::make('transaction_date')
                                    ->label('Tanggal Transaksi')
                                    ->content(fn ($record): string => $record?->transaction?->created_at?->format('d M Y H:i') ?? '-'),
                                Forms\Components\Placeholder::make('customer_name')
                                    ->label('Pelanggan')
                                    ->content(fn ($record): string => $record?->customer?->name ?? '-'),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Item Return')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Placeholder::make('product_name')
                                            ->label('Produk')
                                            ->content(fn ($record): string => $record?->product?->name ?? '-'),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->disabled()
                                            ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.')),
                                        Forms\Components\TextInput::make('subtotal')
                                            ->label('Subtotal')
                                            ->disabled()
                                            ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.')),
                                        Forms\Components\Placeholder::make('exchange_info')
                                            ->label('Tukar Dengan')
                                            ->content(function ($record): string {
                                                if (! $record?->hasExchange()) {
                                                    return '-';
                                                }

                                                $name = $record->exchangeProduct?->name ?? '-';
                                                $qty = $record->exchange_quantity ?? 0;

                                                return "{$name} ({$qty}x)";
                                            }),
                                    ])
                                    ->columns(5)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan Tambahan')
                                    ->disabled()
                                    ->rows(2),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pembayaran')
                            ->schema([
                                Forms\Components\Placeholder::make('refund_method')
                                    ->label('Metode Refund')
                                    ->content(fn ($record): string => $record?->refund_method?->getLabel() ?? '-'),
                                Forms\Components\Placeholder::make('total_refund')
                                    ->label('Total Refund')
                                    ->content(fn ($record): string => 'Rp '.number_format($record?->total_refund ?? 0, 0, ',', '.')),
                                Forms\Components\Placeholder::make('total_exchange_value')
                                    ->label('Total Nilai Tukar')
                                    ->content(fn ($record): string => 'Rp '.number_format($record?->total_exchange_value ?? 0, 0, ',', '.')),
                                Forms\Components\Placeholder::make('selisih_amount')
                                    ->label('Selisih')
                                    ->content(function ($record): string {
                                        $amount = $record?->selisih_amount ?? 0;
                                        $type = $amount > 0 ? 'Dibayar' : ($amount < 0 ? 'Dikembalikan' : 'Nol');

                                        return "{$type}: Rp ".number_format(abs($amount), 0, ',', '.');
                                    }),
                                Forms\Components\Placeholder::make('points_reversed')
                                    ->label('Poin Dibatalkan')
                                    ->content(fn ($record): string => number_format($record?->points_reversed ?? 0, 0, ',', '.')),
                                Forms\Components\Placeholder::make('points_returned')
                                    ->label('Poin Dikembalikan')
                                    ->content(fn ($record): string => number_format($record?->points_returned ?? 0, 0, ',', '.')),
                                Forms\Components\Placeholder::make('user_name')
                                    ->label('Diproses Oleh')
                                    ->content(fn ($record): string => $record?->user?->name ?? '-'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('Nomor Return')
                    ->searchable(),
                Tables\Columns\TextColumn::make('return_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (ReturnType $state): string => $state->getLabel())
                    ->color(fn (ReturnType $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('reason_category')
                    ->label('Alasan')
                    ->badge()
                    ->formatStateUsing(fn (ReturnReason $state): string => $state->getLabel())
                    ->color(fn (ReturnReason $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('total_refund')
                    ->label('Total Refund')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('refund_method')
                    ->label('Metode Refund')
                    ->badge()
                    ->formatStateUsing(fn (?RefundMethod $state): string => $state?->getLabel() ?? '-')
                    ->color(fn (?RefundMethod $state): string => $state?->getColor() ?? 'gray'),
                Tables\Columns\TextColumn::make('points_reversed')
                    ->label('Poin Dibatalkan')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('points_returned')
                    ->label('Poin Dikembalikan')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diproses Oleh')
                    ->searchable(),
            ])
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Return')
                    ->options(collect(ReturnType::cases())->mapWithKeys(fn (ReturnType $type) => [$type->value => $type->getLabel()])),
                SelectFilter::make('refund_method')
                    ->label('Metode Refund')
                    ->options(collect(RefundMethod::cases())->mapWithKeys(fn (RefundMethod $method) => [$method->value => $method->getLabel()])),
                SelectFilter::make('reason_category')
                    ->label('Alasan')
                    ->options(collect(ReturnReason::cases())->mapWithKeys(fn (ReturnReason $reason) => [$reason->value => $reason->getLabel()])),
                Filter::make('return_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('return_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('return_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Dari '.\Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Sampai '.\Carbon\Carbon::parse($data['to'])->format('d M Y');
                        }

                        return $indicators;
                    })
                    ->columns(2),
            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
                Tables\Actions\Action::make('print')
                    ->label('Cetak Struk')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->requiresConfirmation(false)
                    ->url(fn ($record): string => '#')
                    ->extraAttributes(fn ($record): array => [
                        'onclick' => "window.printReturnReceipt({$record->id}); return false;",
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReturns::route('/'),
            'view' => Pages\ViewProductReturn::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
