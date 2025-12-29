<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record) => $record->status === 'pending'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        TextEntry::make('payment_number')->label('Nomor Pembayaran'),
                        TextEntry::make('bill.bill_number')->label('Nomor Tagihan'),
                        TextEntry::make('bill.user.name')->label('Penghuni'),
                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR', true),
                        TextEntry::make('paymentMethod.kind')
                            ->label('Metode')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer',
                                'qris' => 'QRIS',
                                default => $state,
                            }),
                        TextEntry::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->date('d M Y'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pending' => 'Menunggu',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                                default => $state,
                            })
                            ->color(fn($state) => match ($state) {
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Section::make('Bukti Pembayaran')
                    ->schema([
                        ImageEntry::make('proof_path')
                            ->label('')
                            ->placeholder('Tidak ada bukti')
                            ->height(400),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => !$record->proof_path),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('-'),
                    ])
                    ->visible(fn($record) => !empty($record->notes))
                    ->collapsible(),

                Section::make('Verifikasi')
                    ->schema([
                        TextEntry::make('verifiedBy.name')
                            ->label('Diverifikasi Oleh')
                            ->placeholder('-'),
                        TextEntry::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->placeholder('-')
                            ->visible(fn($record) => $record->status === 'rejected'),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => in_array($record->status, ['verified', 'rejected']))
                    ->collapsible(),
            ]);
    }
}
