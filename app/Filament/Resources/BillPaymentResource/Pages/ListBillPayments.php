<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'pending'))
                ->badge(fn() => static::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'verified' => Tab::make('Terverifikasi')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'verified'))
                ->badge(fn() => static::getModel()::where('status', 'verified')->count())
                ->badgeColor('success'),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'rejected'))
                ->badge(fn() => static::getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}
