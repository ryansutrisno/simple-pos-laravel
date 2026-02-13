<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtPaymentResource\Pages;
use App\Models\DebtPayment;
use App\Models\SupplierDebt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DebtPaymentResource extends Resource
{
    protected static ?string $model = DebtPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pembayaran Hutang';

    protected static ?string $pluralLabel = 'Pembayaran Hutang';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('supplier_debt_id')
                            ->label('Hutang')
                            ->options(function () {
                                return SupplierDebt::whereNotIn('status', ['paid'])
                                    ->with('supplier')
                                    ->get()
                                    ->mapWithKeys(function ($debt) {
                                        return [$debt->id => "{$debt->debt_number} - {$debt->supplier->name} (Rp ".number_format($debt->remaining_amount, 0, ',', '.').')'];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $debt = SupplierDebt::find($state);
                                if ($debt) {
                                    $set('amount', $debt->remaining_amount);
                                }
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'qris' => 'QRIS',
                            ])
                            ->default('cash')
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Lainnya')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('debt.debt_number')
                    ->label('No. Hutang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('debt.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('payment_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('payment_date', '<=', $data['to']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDebtPayments::route('/'),
            'create' => Pages\CreateDebtPayment::route('/create'),
            'view' => Pages\ViewDebtPayment::route('/{record}'),
            'edit' => Pages\EditDebtPayment::route('/{record}/edit'),
        ];
    }
}
