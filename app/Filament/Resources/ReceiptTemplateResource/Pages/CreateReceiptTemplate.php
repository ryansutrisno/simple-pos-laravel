<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use App\Services\ReceiptTemplateService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateReceiptTemplate extends CreateRecord
{
    protected static string $resource = ReceiptTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate template data before creating
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
