<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\RoomType;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
