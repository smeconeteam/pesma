<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\RoomType;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $roomType = RoomType::find($data['room_type_id']);

        // Jika user tidak mengisi capacity, gunakan default dari room type
        // Jika user sudah mengisi (baik dari auto-fill atau manual), biarkan apa adanya
        if (blank($data['capacity'] ?? null)) {
            $data['capacity'] = $roomType?->default_capacity;
        }

        // Jika user tidak mengisi monthly_rate, gunakan default dari room type
        // Jika user sudah mengisi (baik dari auto-fill atau manual), biarkan apa adanya
        if (blank($data['monthly_rate'] ?? null)) {
            $data['monthly_rate'] = $roomType?->default_monthly_rate;
        }

        return $data;
    }
}