<?php

namespace App\Filament\Resources\DormResource\Pages;

use App\Filament\Resources\DormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDorm extends EditRecord
{
    protected static string $resource = DormResource::class;

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
}
