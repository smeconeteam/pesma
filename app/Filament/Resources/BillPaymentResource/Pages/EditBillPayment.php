<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use App\Filament\Resources\BillPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillPayment extends EditRecord
{
    protected static string $resource = BillPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
