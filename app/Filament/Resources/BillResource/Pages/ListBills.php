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
            Actions\Action::make('generate_room_bills')
                ->label('Generate Tagihan Kamar')
                ->icon('heroicon-o-building-office-2')
                ->color('success')
                ->url(BillResource::getUrl('generate-room')),

            Actions\Action::make('generate_resident_bills')
                ->label('Generate Tagihan Penghuni')
                ->icon('heroicon-o-users')
                ->color('info')
                ->url(BillResource::getUrl('generate-resident')),

            Actions\CreateAction::make()
                ->label('Buat Tagihan Individual'),
        ];
    }
}
