<?php

namespace App\Filament\Resources\RoomTypeResource\Pages;

use App\Filament\Resources\RoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoomType extends EditRecord
{
    protected static string $resource = RoomTypeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Generate kode dari nama
        $data['code'] = RoomTypeResource::buildCode(
            $data['name'] ?? null
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(
                    fn(): bool =>
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
