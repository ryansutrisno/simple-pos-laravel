<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierDebtResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDebt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierDebtResource extends Resource
{
    protected static ?string $model = SupplierDebt::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Hutang Supplier';

    protected static ?string $pluralLabel = 'Hutang Supplier';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Hutang')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->options(Supplier::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('purchase_order_id', null);
                            }),
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Purchase Order')
                            ->options(function (Forms\Get $get) {
                                $supplierId = $get('supplier_id');
                                if (! $supplierId) {
                                    return [];
                                }

                                return PurchaseOrder::where('supplier_id', $supplierId)
                                    ->where('status', 'received')
                                    ->whereDoesntHave('debt')
                                    ->pluck('order_number', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText('Opsional, pilih jika hutang terkait PO tertentu'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Hutang')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $paid = $get('paid_amount') ?? 0;
                                $set('remaining_amount', $state - $paid);
                            }),
                        Forms\Components\DatePicker::make('debt_date')
                            ->label('Tanggal Hutang')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $total = $get('total_amount') ?? 0;
                                $remaining = $total - $state;
                                $set('remaining_amount', $remaining >= 0 ? $remaining : 0);
                            }),
                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('Sisa Hutang')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->prefix('Rp'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Lainnya')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
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
                Tables\Columns\TextColumn::make('debt_number')
                    ->label('No. Hutang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Hutang')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => match ($state->value) {
                        'pending' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Belum Dibayar',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->remaining_amount > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->default(fn ($record) => $record->remaining_amount),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'qris' => 'QRIS',
                            ])
                            ->default('cash')
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->payments()->create([
                            'amount' => $data['amount'],
                            'payment_date' => now(),
                            'payment_method' => $data['payment_method'],
                            'note' => $data['note'] ?? null,
                            'user_id' => auth()->id() ?? 1,
                        ]);
                    }),
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
            'index' => Pages\ListSupplierDebts::route('/'),
            'create' => Pages\CreateSupplierDebt::route('/create'),
            'view' => Pages\ViewSupplierDebt::route('/{record}'),
            'edit' => Pages\EditSupplierDebt::route('/{record}/edit'),
        ];
    }
}
