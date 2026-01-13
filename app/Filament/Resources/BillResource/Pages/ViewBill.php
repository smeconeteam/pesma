<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record) => $record->status === 'issued' && $record->paid_amount == 0),

            Actions\DeleteAction::make()
                ->visible(fn($record) => $record->canBeDeleted()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('📋 Informasi Tagihan')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('bill_number')
                            ->label('No. Tagihan')
                            ->copyable()
                            ->copyMessage('Nomor tagihan disalin!')
                            ->icon('heroicon-o-clipboard-document')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn($record) => $record->status_color)
                            ->formatStateUsing(fn($record) => $record->status_label)
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('payment_percentage')
                            ->label('Progress Pembayaran')
                            ->formatStateUsing(fn($record) => $record->payment_percentage . '%')
                            ->badge()
                            ->color(fn($record) => match (true) {
                                $record->payment_percentage == 100 => 'success',
                                $record->payment_percentage >= 50 => 'info',
                                $record->payment_percentage > 0 => 'warning',
                                default => 'gray'
                            })
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('user.residentProfile.full_name')
                            ->label('Penghuni')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('billingType.name')
                            ->label('Jenis Tagihan')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('room.code')
                            ->label('Kamar')
                            ->default('-')
                            ->icon('heroicon-o-home'),

                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Jatuh Tempo')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar')
                            ->color(fn($record) => $record->isOverdue() ? 'danger' : null)
                            ->weight(fn($record) => $record->isOverdue() ? 'bold' : null),
                    ]),

                Infolists\Components\Section::make('💰 Rincian Nominal')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('base_amount')
                            ->label('Nominal Dasar')
                            ->money('IDR')
                            ->icon('heroicon-o-calculator'),

                        Infolists\Components\TextEntry::make('discount_percent')
                            ->label('Diskon')
                            ->formatStateUsing(fn($state) => $state . '%')
                            ->icon('heroicon-o-tag'),

                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label('Nominal Diskon')
                            ->money('IDR')
                            ->color('success')
                            ->icon('heroicon-o-minus-circle'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Tagihan')
                            ->money('IDR')
                            ->weight('bold')
                            ->size('lg')
                            ->icon('heroicon-o-currency-dollar'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->money('IDR')
                            ->color('success')
                            ->weight('bold')
                            ->icon('heroicon-o-check-circle'),

                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('Sisa Tagihan')
                            ->money('IDR')
                            ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                            ->weight('bold')
                            ->size('lg')
                            ->icon(fn($state) => $state > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge'),
                    ]),

                Infolists\Components\Section::make('📅 Periode')
                    ->columns(2)
                    ->visible(fn($record) => $record->period_start || $record->period_end)
                    ->schema([
                        Infolists\Components\TextEntry::make('period_start')
                            ->label('Periode Mulai')
                            ->date('d F Y')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('period_end')
                            ->label('Periode Selesai')
                            ->date('d F Y')
                            ->default('-'),
                    ]),

                Infolists\Components\Section::make('📝 Detail Item')
                    ->visible(fn($record) => $record->details()->exists())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi'),

                                Infolists\Components\TextEntry::make('base_amount')
                                    ->label('Nominal Dasar')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('discount_amount')
                                    ->label('Diskon')
                                    ->money('IDR')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Total')
                                    ->money('IDR')
                                    ->weight('bold'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make('💳 Riwayat Pembayaran')
                    ->visible(fn($record) => $record->payments()->exists())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_number')
                                    ->label('No. Pembayaran')
                                    ->copyable()
                                    ->icon('heroicon-o-clipboard-document'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Jumlah')
                                    ->money('IDR')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('payment_date')
                                    ->label('Tanggal')
                                    ->date('d F Y'),

                                Infolists\Components\TextEntry::make('paid_by_name')
                                    ->label('Dibayar Oleh')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('paymentMethod.kind')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'transfer' => 'Transfer',
                                        'qris' => 'QRIS',
                                        'cash' => 'Cash',
                                        default => $state
                                    }),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($record) => $record->status_color)
                                    ->formatStateUsing(fn($record) => $record->status_label),
                            ])
                            ->columns(6),
                    ]),

                Infolists\Components\Section::make('📝 Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->default('-')
                            ->prose(),
                    ])
                    ->visible(fn($record) => $record->notes),

                Infolists\Components\Section::make('ℹ️ Informasi Tambahan')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('issuedBy.name')
                            ->label('Dibuat Oleh')
                            ->default('-')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('issued_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d F Y H:i')
                            ->default('-')
                            ->icon('heroicon-o-clock'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->dateTime('d F Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
            ]);
    }
}
