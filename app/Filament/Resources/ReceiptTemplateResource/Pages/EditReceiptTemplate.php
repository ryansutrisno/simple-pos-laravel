<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use App\Services\ReceiptTemplateService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditReceiptTemplate extends EditRecord
{
    protected static string $resource = ReceiptTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate template data before saving
        $templateService = new ReceiptTemplateService();
        $errors = $templateService->validateTemplateData($data['template_data'] ?? []);

        if (!empty($errors)) {
            Notification::make()
                ->title('Template Validation Failed')
                ->body(implode('<br>', $errors))
                ->danger()
                ->send();
            
            throw new \Exception('Template validation failed');
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
