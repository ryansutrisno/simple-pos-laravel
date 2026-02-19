<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Purchase Order';

    protected static ?string $pluralLabel = 'Purchase Order';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi PO')
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'name', fn ($query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Pilih supplier')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Supplier')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Telepon')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('address')
                                            ->label('Alamat')
                                            ->rows(2),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true),
                                    ])
                                    ->createOptionUsing(fn (array $data) => Supplier::create($data)->id),
                                Forms\Components\DatePicker::make('order_date')
                                    ->label('Tanggal Pesan')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('expected_date')
                                    ->label('Tanggal Estimasi'),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Item Pesanan')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Produk')
                                            ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $product = Product::find($state);
                                                if ($product) {
                                                    $set('purchase_price', $product->purchase_price);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $price = $get('purchase_price') ?? 0;
                                                $set('subtotal', $state * $price);
                                            }),
                                        Forms\Components\TextInput::make('purchase_price')
                                            ->label('Harga Beli')
                                            ->numeric()
                                            ->required()
                                            ->prefix('Rp')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $quantity = $get('quantity') ?? 0;
                                                $set('subtotal', $state * $quantity);
                                            }),
                                        Forms\Components\TextInput::make('subtotal')
                                            ->label('Subtotal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(0),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->reorderable(false)
                                    ->addActionLabel('Tambah Item')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $items = $get('items') ?? [];
                                        $total = collect($items)->sum('subtotal');
                                        $set('total_amount', $total);
                                    }),
                            ]),

                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Ringkasan')
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->label('Nomor PO')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visibleOn('edit'),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(PurchaseOrderStatus::class)
                                    ->enum(PurchaseOrderStatus::class)
                                    ->default(PurchaseOrderStatus::Draft)
                                    ->disabled(fn ($record) => $record && ! $record->canUpdate()),
                                Forms\Components\Select::make('payment_status')
                                    ->label('Status Pembayaran')
                                    ->options(PaymentStatus::class)
                                    ->enum(PaymentStatus::class)
                                    ->default(PaymentStatus::Unpaid),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                                Forms\Components\DatePicker::make('received_date')
                                    ->label('Tanggal Diterima')
                                    ->visibleOn('edit'),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Dibuat')
                                    ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-')
                                    ->visibleOn('edit'),
                            ]),

                        Forms\Components\Section::make('User')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Dibuat Oleh')
                                    ->relationship('user', 'name')
                                    ->default(auth()->id())
                                    ->required()
                                    ->disabled(),
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
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PurchaseOrderStatus $state) => $state->getLabel())
                    ->color(fn (PurchaseOrderStatus $state) => $state->getColor()),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->getLabel())
                    ->color(fn (PaymentStatus $state) => $state->getColor()),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tgl Pesan')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expected_date')
                    ->label('Estimasi')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PurchaseOrderStatus::class),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(PaymentStatus::class),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name'),
                Filter::make('order_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->visible(fn (PurchaseOrder $record) => $record->canUpdate()),
                Tables\Actions\Action::make('markAsPending')
                    ->label('Ajukan')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft)
                    ->action(function (PurchaseOrder $record) {
                        $record->update([
                            'status' => PurchaseOrderStatus::Pending,
                            'order_date' => now(),
                        ]);
                        Notification::make()
                            ->title('PO diajukan')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('markAsOrdered')
                    ->label('Pesan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => $record->canMarkAsOrdered())
                    ->action(function (PurchaseOrder $record) {
                        $record->update([
                            'status' => PurchaseOrderStatus::Ordered,
                            'expected_date' => now()->addDays(7),
                        ]);
                        Notification::make()
                            ->title('PO dipesan ke supplier')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('receive')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => $record->canReceive())
                    ->form([
                        Forms\Components\Repeater::make('items')
                            ->label('Item')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Produk')
                                    ->disabled(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Dipesan')
                                    ->disabled(),
                                Forms\Components\TextInput::make('quantity_received')
                                    ->label('Diterima')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->disableItemMovement()
                            ->addable(false)
                            ->deletable(false),
                    ])
                    ->fillForm(function (PurchaseOrder $record) {
                        return [
                            'items' => $record->items->map(fn ($item) => [
                                'id' => $item->id,
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'quantity_received' => $item->quantity,
                            ])->toArray(),
                        ];
                    })
                    ->action(function (PurchaseOrder $record, array $data) {
                        foreach ($data['items'] as $itemData) {
                            $item = $record->items()->find($itemData['id']);
                            $item->update(['quantity_received' => $itemData['quantity_received']]);

                            $product = $item->product;
                            $product->increment('stock', $itemData['quantity_received']);
                        }

                        $record->update([
                            'status' => PurchaseOrderStatus::Received,
                            'received_date' => now(),
                        ]);

                        Notification::make()
                            ->title('PO diterima, stok diperbarui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => in_array($record->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Pending, PurchaseOrderStatus::Ordered]))
                    ->action(function (PurchaseOrder $record) {
                        $record->update(['status' => PurchaseOrderStatus::Cancelled]);
                        Notification::make()
                            ->title('PO dibatalkan')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (PurchaseOrder $record) => $record->canDelete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('deleteAny', PurchaseOrder::class)),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
