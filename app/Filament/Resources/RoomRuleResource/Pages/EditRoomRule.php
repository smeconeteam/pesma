<?php

namespace App\Filament\Resources\RoomRuleResource\Pages;

use App\Filament\Resources\RoomRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoomRule extends EditRecord
{
    protected static string $resource = RoomRuleResource::class;

    protected static ?string $title = 'Ubah Peraturan Kamar';

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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Peraturan berhasil diperbarui';
    }
}
