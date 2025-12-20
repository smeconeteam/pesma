<?php

namespace App\Filament\Resources\AdminAssignmentResource\Pages;

use App\Filament\Resources\AdminAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminAssignments extends ListRecords
{
    protected static string $resource = AdminAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Angkat Admin'),
        ];
    }
}
