<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Resources\Pages\ListRecords;

class ListPolicies extends ListRecords
{
    protected static string $resource = PolicyResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->redirect(PolicyResource::getUrl('active'));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
