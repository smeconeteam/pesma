<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $roomType = RoomType::find($data['room_type_id']);

        if (empty($data['capacity'])) {
            $data['capacity'] = $roomType?->default_capacity;
        }

        if (empty($data['monthly_rate'])) {
            $data['monthly_rate'] = $roomType?->default_monthly_rate;
        }

        return $data;
    }
}
