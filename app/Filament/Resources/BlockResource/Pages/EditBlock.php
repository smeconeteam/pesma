<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlock extends EditRecord
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->label('Hapus')
                ->visible(fn (): bool =>
                    auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin'])
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
