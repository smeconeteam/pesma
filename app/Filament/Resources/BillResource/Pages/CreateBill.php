<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\Bill;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['bill_number'] = Bill::generateBillNumber($data['bill_type'] ?? 'custom');
        $data['issued_at'] = now();
        $data['status'] = 'pending';
        $data['total_amount'] = 0;
        $data['discount_amount'] = 0;
        $data['final_amount'] = 0;
        $data['paid_amount'] = 0;

        return $data;
    }
}