<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => !$record->bill_payment_id),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Jenis Transaksi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'income' => 'success',
                                'expense' => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'income' => 'Pemasukan',
                                'expense' => 'Pengeluaran',
                            }),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama Transaksi'),

                        Infolists\Components\TextEntry::make('amount')
                            ->label('Jumlah')
                            ->money('IDR')
                            ->size('lg')
                            ->weight('bold')
                            ->color(fn ($record): string => $record->type === 'income' ? 'success' : 'danger'),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'cash' => 'Tunai',
                                'credit' => 'Transfer',
                            }),

                        Infolists\Components\TextEntry::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('billPayment.payment_number')
                            ->label('Dari Pembayaran Billing')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->bill_payment_id !== null),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Lokasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('dorm.name')
                            ->label('Cabang')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('block.name')
                            ->label('Komplek')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y, H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diubah')
                            ->dateTime('d F Y, H:i'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}