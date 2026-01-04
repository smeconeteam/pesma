<?php

namespace App\Filament\Resources\RoomTypeResource\Pages;

use App\Filament\Resources\RoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoomType extends EditRecord
{
    protected static string $resource = RoomTypeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // "vip-room" => base_name = "vip room"
        $name = (string) ($data['name'] ?? '');
        $data['base_name'] = str_replace('-', ' ', $name);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = RoomTypeResource::buildAutoName(
            $data['base_name'] ?? null
        );

        unset($data['base_name']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (): bool =>
                    auth()->user()?->hasRole(['super_admin', 'main_admin'])
                    && ! $this->record->trashed()
                    && ! $this->record->rooms()->exists()
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
