<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\BillPaymentResource;

class ListBillPayments extends ListRecords
{
    protected static string $resource = BillPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Pembayaran')
        ];
    }
}
