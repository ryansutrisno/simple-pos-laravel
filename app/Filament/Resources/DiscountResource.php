<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Diskon';

    protected static ?string $modelLabel = 'Diskon';

    protected static ?string $pluralModelLabel = 'Diskon';

    protected static ?string $navigationGroup = 'Manajemen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Diskon')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Voucher')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'voucher')
                            ->helperText('Kosongkan untuk diskon non-voucher'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Diskon')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis Diskon')
                            ->options([
                                'percentage' => 'Persentase (%)',
                                'fixed' => 'Nominal Tetap',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->label(fn (Forms\Get $get) => $get('type') === 'percentage' ? 'Persentase' : 'Nominal')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : null),
                        Forms\Components\TextInput::make('min_purchase')
                            ->label('Minimum Pembelian')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Maksimum Diskon')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'percentage'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Target Diskon')
                    ->schema([
                        Forms\Components\Select::make('target_type')
                            ->label('Tipe Target')
                            ->options([
                                'product' => 'Produk Tertentu',
                                'category' => 'Kategori Tertentu',
                                'global' => 'Semua Produk',
                                'voucher' => 'Voucher',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\MultiSelect::make('products')
                            ->label('Pilih Produk')
                            ->options(Product::pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'product')
                            ->relationship('products', 'name'),
                        Forms\Components\MultiSelect::make('categories')
                            ->label('Pilih Kategori')
                            ->options(Category::pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'category')
                            ->relationship('categories', 'name'),
                    ]),

                Forms\Components\Section::make('Masa Berlaku')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->after('start_date'),
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Batas Penggunaan')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Kosongkan untuk tanpa batas'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Persentase',
                        'fixed' => 'Tetap',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(function (Discount $record): string {
                        if ($record->type === 'percentage') {
                            return $record->value.'%';
                        }

                        return 'Rp '.number_format($record->value, 0, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'product' => 'Produk',
                        'category' => 'Kategori',
                        'global' => 'Global',
                        'voucher' => 'Voucher',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Berakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Tanpa Batas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Terpakai')
                    ->formatStateUsing(fn (Discount $record): string => $record->usage_limit
                        ? "{$record->used_count}/{$record->usage_limit}"
                        : (string) $record->used_count),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Aktif',
                        false => 'Tidak Aktif',
                    ]),
                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Tipe Target')
                    ->options([
                        'product' => 'Produk',
                        'category' => 'Kategori',
                        'global' => 'Global',
                        'voucher' => 'Voucher',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Diskon')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed' => 'Tetap',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
