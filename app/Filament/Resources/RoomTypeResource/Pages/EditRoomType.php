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
        // "VIP 4 orang" => base_name = "VIP"
        $name = (string) ($data['name'] ?? '');
        $data['base_name'] = preg_replace('/\s+\d+\s+orang\s*$/i', '', $name) ?: $name;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = RoomTypeResource::buildAutoName(
            $data['base_name'] ?? null,
            $data['default_capacity'] ?? null
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
