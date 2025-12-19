<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\Country;
use App\Models\RoomResident;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // ✅ pastikan profile ada supaya form tidak kosong saat edit
        $this->record->residentProfile()->firstOrCreate([]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // WNI => auto Indonesia
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
                ->visible(fn (): bool =>
                    ! $this->record->trashed()
                    && ! RoomResident::query()->where('user_id', $this->record->id)->exists()
                ),
        ];
    }
}
