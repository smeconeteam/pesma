<?php

namespace App\Filament\Resources\ResidentCategoryResource\Pages;

use App\Filament\Resources\ResidentCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResidentCategory extends CreateRecord
{
    protected static string $resource = ResidentCategoryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}