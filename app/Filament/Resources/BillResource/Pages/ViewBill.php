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
            Actions\Action::make('issue')
                ->label('Issue Tagihan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => $record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsIssued(auth()->user());
                    \Filament\Notifications\Notification::make()
                        ->title('Tagihan berhasil di-issue')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\EditAction::make()
                ->visible(fn($record) => $record->status === 'draft'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Tagihan')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('bill_number')
                            ->label('No. Tagihan'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn($record) => $record->status_color)
                            ->formatStateUsing(fn($record) => $record->status_label),

                        Infolists\Components\TextEntry::make('user.residentProfile.full_name')
                            ->label('Penghuni'),

                        Infolists\Components\TextEntry::make('billingType.name')
                            ->label('Jenis Tagihan'),

                        Infolists\Components\TextEntry::make('room.code')
                            ->label('Kamar')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Jatuh Tempo')
                            ->date('d F Y'),
                    ]),

                Infolists\Components\Section::make('Rincian Nominal')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('base_amount')
                            ->label('Nominal Dasar')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('discount_percent')
                            ->label('Diskon')
                            ->formatStateUsing(fn($state) => $state . '%'),

                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label('Nominal Diskon')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Tagihan')
                            ->money('IDR')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->money('IDR')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('Sisa Tagihan')
                            ->money('IDR')
                            ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('payment_percentage')
                            ->label('Persentase Pembayaran')
                            ->formatStateUsing(fn($record) => $record->payment_percentage . '%')
                            ->color(fn($record) => $record->payment_percentage == 100 ? 'success' : 'warning'),
                    ]),

                Infolists\Components\Section::make('Periode')
                    ->columns(2)
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

                Infolists\Components\Section::make('Detail Item')
                    ->visible(fn($record) => $record->details()->exists())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Nominal')
                                    ->money('IDR'),
                            ])
                            ->columns(2),
                    ]),

                Infolists\Components\Section::make('Riwayat Pembayaran')
                    ->visible(fn($record) => $record->payments()->exists())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_number')
                                    ->label('No. Pembayaran'),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Jumlah')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('payment_date')
                                    ->label('Tanggal')
                                    ->date('d F Y'),

                                Infolists\Components\TextEntry::make('paidBy.name')
                                    ->label('Dibayar Oleh'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($record) => $record->status_color)
                                    ->formatStateUsing(fn($record) => $record->status_label),
                            ])
                            ->columns(5),
                    ]),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->default('-'),
                    ])
                    ->visible(fn($record) => $record->notes),

                Infolists\Components\Section::make('Informasi Tambahan')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('issuedBy.name')
                            ->label('Di-issue Oleh')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('issued_at')
                            ->label('Tanggal Issue')
                            ->dateTime('d F Y H:i')
                            ->default('-'),
                    ]),
            ]);
    }
}
