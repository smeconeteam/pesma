<?php

namespace App\Filament\Resources\RoomRuleResource\Pages;

use App\Filament\Resources\RoomRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRoomRule extends CreateRecord
{
    protected static string $resource = RoomRuleResource::class;

    protected static ?string $title = 'Buat Peraturan Kamar';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Peraturan berhasil dibuat';
    }
}
