<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah')
                ->visible(fn () => $this->record->canUpdate()),
            Actions\Action::make('markAsPending')
                ->label('Ajukan')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === PurchaseOrderStatus::Draft)
                ->action(function () {
                    $this->record->update([
                        'status' => PurchaseOrderStatus::Pending,
                        'order_date' => now(),
                    ]);
                    Notification::make()
                        ->title('PO diajukan')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('markAsOrdered')
                ->label('Pesan')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canMarkAsOrdered())
                ->action(function () {
                    $this->record->update([
                        'status' => PurchaseOrderStatus::Ordered,
                        'expected_date' => now()->addDays(7),
                    ]);
                    Notification::make()
                        ->title('PO dipesan ke supplier')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('receive')
                ->label('Terima Barang')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canReceive())
                ->form([
                    \Filament\Forms\Components\Repeater::make('items')
                        ->label('Item')
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('id'),
                            \Filament\Forms\Components\TextInput::make('product_name')
                                ->label('Produk')
                                ->disabled(),
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->label('Dipesan')
                                ->disabled(),
                            \Filament\Forms\Components\TextInput::make('quantity_received')
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
                ->fillForm(function () {
                    return [
                        'items' => $this->record->items->map(fn ($item) => [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'quantity_received' => $item->quantity,
                        ])->toArray(),
                    ];
                })
                ->action(function (array $data) {
                    foreach ($data['items'] as $itemData) {
                        $item = $this->record->items()->find($itemData['id']);
                        $item->update(['quantity_received' => $itemData['quantity_received']]);

                        $product = $item->product;
                        $product->increment('stock', $itemData['quantity_received']);
                    }

                    $this->record->update([
                        'status' => PurchaseOrderStatus::Received,
                        'received_date' => now(),
                    ]);

                    Notification::make()
                        ->title('PO diterima, stok diperbarui')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Pending, PurchaseOrderStatus::Ordered]))
                ->action(function () {
                    $this->record->update(['status' => PurchaseOrderStatus::Cancelled]);
                    Notification::make()
                        ->title('PO dibatalkan')
                        ->warning()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\DeleteAction::make()->label('Hapus')
                ->visible(fn () => $this->record->canDelete()),
        ];
    }
}
