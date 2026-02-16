<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\CustomerPoint;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ViewCustomer extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi Pelanggan')
                    ->schema([
                        TextEntry::make('name')->label('Nama'),
                        TextEntry::make('phone')->label('Telepon'),
                        TextEntry::make('email')->label('Email')->default('-'),
                        TextEntry::make('address')->label('Alamat')->default('-'),
                    ])
                    ->columns(2),

                Section::make('Statistik')
                    ->schema([
                        TextEntry::make('points')
                            ->label('Poin Saat Ini')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('total_spent')
                            ->label('Total Belanja')
                            ->money('IDR'),
                        TextEntry::make('total_transactions')
                            ->label('Total Transaksi')
                            ->badge(),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif'),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return CustomerPoint::query()
            ->where('customer_id', $this->record->id)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Tanggal')
                ->dateTime('d M Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('type')
                ->label('Tipe')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'earn' => 'success',
                    'redeem' => 'warning',
                    'adjust' => 'info',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'earn' => 'Diperoleh',
                    'redeem' => 'Ditukar',
                    'adjust' => 'Penyesuaian',
                }),
            Tables\Columns\TextColumn::make('amount')
                ->label('Jumlah Poin')
                ->formatStateUsing(fn (CustomerPoint $record): string => ($record->type === 'redeem' ? '-' : '+').$record->amount),
            Tables\Columns\TextColumn::make('balance_after')
                ->label('Saldo Akhir')
                ->badge(),
            Tables\Columns\TextColumn::make('description')
                ->label('Keterangan')
                ->default('-'),
        ];
    }
}
