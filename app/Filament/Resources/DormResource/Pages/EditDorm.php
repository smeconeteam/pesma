<?php

namespace App\Filament\Resources\DormResource\Pages;

use App\Filament\Resources\DormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Dorm;

class EditDorm extends EditRecord
{
    protected static string $resource = DormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->label('Hapus')
                ->visible(fn (): bool =>
                    auth()->user()?->hasRole(['super_admin', 'main_admin'])
                    && ! $this->record->trashed()
                    && ! $this->record->blocks()->exists()
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
