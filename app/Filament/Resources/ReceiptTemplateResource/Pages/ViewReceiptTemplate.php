<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use App\Services\ReceiptTemplateService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class ViewReceiptTemplate extends ViewRecord
{
    protected static string $resource = ReceiptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('preview')
                ->label('Preview Template')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->action(function () {
                    $templateService = new ReceiptTemplateService();
                    $preview = $templateService->getTemplatePreview($this->record);
                    
                    // In a real implementation, this would open a modal or redirect to preview
                    // For now, we'll just show a notification
                    \Filament\Notifications\Notification::make()
                        ->title('Template Preview')
                        ->body('Preview functionality would open here')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Template Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('store.name')
                            ->label('Store')
                            ->default('Global Template'),
                        TextEntry::make('is_default')
                            ->label('Is Default')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('is_active')
                            ->label('Is Active')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(2),

                Section::make('Template Configuration')
                    ->schema([
                        TextEntry::make('template_data.header.show_logo')
                            ->label('Show Logo')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('template_data.header.show_store_name')
                            ->label('Show Store Name')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('template_data.header.custom_header_message')
                            ->label('Custom Header Message'),
                        
                        TextEntry::make('template_data.body.item_format')
                            ->label('Item Format'),
                        TextEntry::make('template_data.body.show_cashier_name')
                            ->label('Show Cashier')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        
                        TextEntry::make('template_data.footer.custom_footer_message')
                            ->label('Custom Footer Message'),
                        TextEntry::make('template_data.footer.show_barcode')
                            ->label('Show Barcode')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        
                        TextEntry::make('template_data.styling.font_size')
                            ->label('Font Size'),
                        TextEntry::make('template_data.styling.text_alignment')
                            ->label('Text Alignment'),
                        TextEntry::make('template_data.styling.separator_style')
                            ->label('Separator Style'),
                    ])
                    ->columns(2),
            ]);
    }
}
