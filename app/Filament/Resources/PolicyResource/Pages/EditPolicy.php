<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getRedirectUrl(): string
    {
        // Setelah edit -> balik ke view aktif
        return PolicyResource::getUrl('active');
    }
}
