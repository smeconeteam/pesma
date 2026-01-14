<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_room')
                ->label('Generate Tagihan Kamar')
                ->color('success')
                ->url(BillResource::getUrl('create-room')),

            Actions\CreateAction::make()
                ->label('Buat Tagihan'),
        ];
    }
}
