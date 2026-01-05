<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Laporan Transaksi';

    protected static ?string $pluralLabel = 'Laporan Transaksi';

    protected static ?string $navigationGroup = 'Laporan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Transaksi')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('Kasir')
                                    ->required(),
                                Forms\Components\TextInput::make('total')
                                    ->label('Total Pembayaran')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->prefix('Rp'),
                                Forms\Components\Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer Bank',
                                        'qris' => 'QRIS'
                                    ])
                                    ->required()
                                    ->default('cash'),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Detail Item')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->label('Produk')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $product = \App\Models\Product::find($state);
                                                if ($product) {
                                                    $set('price', $product->price);
                                                    $quantity = $set('quantity', 1);
                                                    $set('subtotal', $product->price * $quantity);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                                $price = $get('price');
                                                $set('subtotal', $price * $state);
                                            }),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Harga')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('subtotal')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->reorderable(false)
                                    ->addActionLabel('Tambah Item')
                                    ->itemLabel('Item Transaksi')
                                    ->live()
                            ])
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Ringkasan')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Tanggal Transaksi')
                                    ->content(fn($record): string => $record ? $record->created_at->format('d F Y H:i') : '-'),
                                Forms\Components\Placeholder::make('subtotal')
                                    ->label('Subtotal')
                                    ->content(fn($record): string => $record ? 'Rp ' . number_format($record->total, 0, ',', '.') : 'Rp 0'),
                                Forms\Components\Placeholder::make('total')
                                    ->label('Total')
                                    ->content(fn($record): string => $record ? 'Rp ' . number_format($record->total, 0, ',', '.') : 'Rp 0'),
                            ])
                    ])
                    ->columnSpan(['lg' => 1])
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Dari ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Sampai ' . \Carbon\Carbon::parse($data['to'])->format('d M Y');
                        }
                        return $indicators;
                    })
                    ->columns(2)
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
                        'onclick' => "window.printTransactionReceipt({$record->id}); return false;"
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
