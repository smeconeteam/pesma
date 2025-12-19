<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use Filament\Actions;
use App\Models\Country;
use App\Models\RoomResident;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ResidentResource;

class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pastikan relationship data loaded
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle WNI => auto set Indonesia
        if (isset($data['residentProfile'])) {
            if (($data['residentProfile']['citizenship_status'] ?? 'WNI') === 'WNI') {
                $indoId = Country::query()->where('iso2', 'ID')->value('id');
                if ($indoId) {
                    $data['residentProfile']['country_id'] = $indoId;
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(
                    fn(): bool =>
                    ! $this->record->trashed()
                        && ! RoomResident::query()->where('user_id', $this->record->id)->exists()
                ),
        ];
    }
}
