<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillingType extends EditRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load dorm_id untuk edit form
        if (!($data['applies_to_all'] ?? false)) {
            $firstDorm = $this->record->dorms()->first();
            $data['dorm_id'] = $firstDorm?->id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dormId = $data['dorm_id'] ?? null;
        
        unset($data['dorm_id']);
        unset($data['dorm_ids']);

        // Sync dorm jika tidak applies_to_all
        if (!($data['applies_to_all'] ?? false) && $dormId) {
            $this->record->dorms()->sync([$dormId]);
        } elseif ($data['applies_to_all'] ?? false) {
            $this->record->dorms()->detach();
        }

        return $data;
    }
}