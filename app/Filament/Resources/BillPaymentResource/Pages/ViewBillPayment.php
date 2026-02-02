<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\BillPaymentResource;

class ViewBillPayment extends ViewRecord
{
    protected static string $resource = BillPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('receipt')
                ->label('Cetak Bukti')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->visible(fn() => $this->record->status === 'verified')
                ->url(fn() => route('receipt.show', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('verify')
                ->label('Verifikasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn() =>
                    $this->record->status === 'pending' &&
                        auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin'])
                )
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Pembayaran')
                ->modalDescription(fn() => "Verifikasi pembayaran sebesar Rp " . number_format($this->record->amount, 0, ',', '.') . "?")
                ->action(function () {
                    $this->record->verify(auth()->id());

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Pembayaran Diverifikasi')
                        ->body('Pembayaran berhasil diverifikasi dan status tagihan telah diperbarui.')
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn() =>
                    $this->record->status === 'pending' &&
                        auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin'])
                )
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Contoh: Bukti transfer tidak jelas, nominal tidak sesuai, dll.'),
                ])
                ->action(function (array $data) {
                    $this->record->reject($data['rejection_reason'], auth()->id());

                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('Pembayaran Ditolak')
                        ->body('Pembayaran telah ditolak.')
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pembayaran')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_number')
                                    ->label('No. Pembayaran')
                                    ->copyable()
                                    ->weight('bold')
                                    ->icon('heroicon-o-hashtag'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($record) => match ($record->status) {
                                        'pending' => 'warning',
                                        'verified' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn($record) => $record->status_label),

                                Infolists\Components\TextEntry::make('payment_type_label')
                                    ->label('Tipe Pembayaran')
                                    ->badge()
                                    ->color(fn($record) => $record->is_pic_payment ? 'success' : 'info')
                                    ->icon(fn($record) => $record->is_pic_payment ? 'heroicon-o-user-group' : 'heroicon-o-user'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Jumlah Dibayar')
                                    ->money('IDR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('payment_date')
                                    ->label('Tanggal Pembayaran')
                                    ->date('d F Y')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detail Tagihan')
                    ->schema([
                        Infolists\Components\TextEntry::make('bill.bill_number')
                            ->label('No. Tagihan')
                            ->url(fn($record) => route('filament.admin.resources.tagihan.view', $record->bill_id))
                            ->color('primary')
                            ->icon('heroicon-o-document-text'),

                        Infolists\Components\TextEntry::make('bill.billingType.name')
                            ->label('Jenis Tagihan')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('bill.user.residentProfile.full_name')
                            ->label('Penghuni yang Tertagih')
                            ->icon('heroicon-o-user')
                            ->default(fn($record) => $record->bill->user->name ?? '-'),

                        Infolists\Components\TextEntry::make('bill.room.code')
                            ->label('Kamar')
                            ->default('Tidak ada kamar')
                            ->visible(fn($record) => $record->bill->room_id),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('bill.total_amount')
                                    ->label('Total Tagihan')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('bill.paid_amount')
                                    ->label('Sudah Dibayar')
                                    ->money('IDR')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('bill.remaining_amount')
                                    ->label('Sisa Tagihan')
                                    ->money('IDR')
                                    ->color(fn($record) => $record->bill->remaining_amount > 0 ? 'danger' : 'success')
                                    ->weight('bold'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Informasi Pembayar')
                    ->schema([
                        Infolists\Components\TextEntry::make('paid_by_name')
                            ->label('Nama Pembayar')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('paidByUser.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('paidByUser.residentProfile.phone_number')
                            ->label('No. Telepon')
                            ->icon('heroicon-o-phone')
                            ->default('-')
                            ->visible(fn($record) => $record->paidByUser?->residentProfile),
                    ]),

                Infolists\Components\Section::make('Metode Pembayaran')
                    ->schema([
                        Infolists\Components\TextEntry::make('paymentMethod.kind')
                            ->label('Metode')
                            ->badge()
                            ->formatStateUsing(fn($state) => match ($state) {
                                'qris' => 'QRIS',
                                'transfer' => 'Transfer Bank',
                                'cash' => 'Tunai',
                                default => $state,
                            })
                            ->color(fn($state) => match ($state) {
                                'qris' => 'info',
                                'transfer' => 'success',
                                'cash' => 'warning',
                                default => 'gray',
                            }),

                        // Tampilkan info bank jika transfer
                        Infolists\Components\TextEntry::make('bankAccount.bank_name')
                            ->label('Bank')
                            ->visible(fn($record) => $record->paymentMethod->kind === 'transfer' && $record->bankAccount),

                        Infolists\Components\TextEntry::make('bankAccount.account_number')
                            ->label('No. Rekening')
                            ->visible(fn($record) => $record->paymentMethod->kind === 'transfer' && $record->bankAccount)
                            ->copyable(),

                        Infolists\Components\TextEntry::make('bankAccount.account_name')
                            ->label('Atas Nama')
                            ->visible(fn($record) => $record->paymentMethod->kind === 'transfer' && $record->bankAccount),
                    ]),

                Infolists\Components\Section::make('Bukti Pembayaran')
                    ->visible(fn($record) => $record->proof_path)
                    ->schema([
                        Infolists\Components\ImageEntry::make('proof_path')
                            ->label('')
                            ->size('lg')
                            ->extraImgAttributes(['class' => 'rounded-lg shadow-md'])
                            ->visible(fn($record) => $record->proof_path),
                    ]),

                Infolists\Components\Section::make('Catatan')
                    ->visible(fn($record) => $record->notes)
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->markdown()
                            ->prose(),
                    ]),

                Infolists\Components\Section::make('Status Verifikasi')
                    ->visible(fn($record) => $record->status !== 'pending')
                    ->schema([
                        Infolists\Components\TextEntry::make('verifiedBy.name')
                            ->label('Diverifikasi Oleh')
                            ->icon('heroicon-o-user')
                            ->visible(fn($record) => $record->verified_by),

                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-clock')
                            ->visible(fn($record) => $record->verified_at),

                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->color('danger')
                            ->columnSpanFull()
                            ->visible(fn($record) => $record->status === 'rejected' && $record->rejection_reason),
                    ]),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d F Y, H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Diperbarui Pada')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ]),
            ]);
    }
}
