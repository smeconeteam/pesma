<?php

namespace App\Filament\Resources\ResidentCategoryResource\Pages;

use App\Filament\Resources\ResidentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResidentCategory extends EditRecord
{
    protected static string $resource = ResidentCategoryResource::class;

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