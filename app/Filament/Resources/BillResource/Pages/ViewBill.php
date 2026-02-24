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
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Tagihan')
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
                    ]),

                Infolists\Components\Section::make('Rincian Nominal')
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

                Infolists\Components\Section::make('Periode')
                    ->columns(3)
                    ->visible(fn($record) => $record->period_start || $record->period_end)
                    ->schema([
                        Infolists\Components\TextEntry::make('period_start')
                            ->label('Periode Mulai')
                            ->date('d F Y')
                            ->placeholder('-')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('period_end')
                            ->label('Periode Selesai')
                            ->date('d F Y')
                            ->placeholder('-')
                            ->icon('heroicon-o-calendar')
                            ->color(fn($record) => $record->isOverdue() ? 'danger' : null)
                            ->weight(fn($record) => $record->isOverdue() ? 'bold' : null),

                        Infolists\Components\TextEntry::make('period_info')
                            ->label('Keterangan')
                            ->default(function ($record) {
                                if (!$record->period_end) {
                                    return 'Tidak Terbatas';
                                }

                                if ($record->isOverdue()) {
                                    return 'Sudah Jatuh Tempo';
                                }

                                $days = ceil(now()->diffInDays($record->period_end));
                                return $days < 1 ? "Kurang dari 1 hari" : "Sisa {$days} hari";
                            })
                            ->badge()
                            ->color(function ($record) {
                                if (!$record->period_end) return 'gray';
                                if ($record->isOverdue()) return 'danger';
                                return 'info';
                            }),
                    ]),

                Infolists\Components\Section::make('Detail Tagihan Per Bulan')
                    ->description('Rincian tagihan bulanan')
                    ->visible(fn($record) => $record->details()->exists())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('month')
                                    ->label('Bulan Ke-')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('base_amount')
                                    ->label('Nominal Dasar')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('discount_amount')
                                    ->label('Diskon')
                                    ->money('IDR')
                                    ->color('success')
                                    ->visible(fn($state) => $state > 0),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Total')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('primary'),
                            ])
                            ->columns(5)
                            ->grid(1),

                        Infolists\Components\TextEntry::make('details_summary')
                            ->label('Ringkasan')
                            ->html()
                            ->formatStateUsing(function ($record) {
                                $totalDetails = $record->details()->count();
                                $totalAmount = $record->details()->sum('amount');
                                $totalDiscount = $record->details()->sum('discount_amount');

                                return "
                                    <div class='space-y-1'>
                                        <div class='flex justify-between'>
                                            <span>Total Bulan:</span>
                                            <strong>{$totalDetails} Bulan</strong>
                                        </div>
                                        <div class='flex justify-between'>
                                            <span>Total Diskon:</span>
                                            <strong class='text-green-600'>Rp " . number_format($totalDiscount, 0, ',', '.') . "</strong>
                                        </div>
                                        <div class='flex justify-between border-t pt-1'>
                                            <span>Total Tagihan:</span>
                                            <strong class='text-lg'>Rp " . number_format($totalAmount, 0, ',', '.') . "</strong>
                                        </div>
                                    </div>
                                ";
                            })
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Riwayat Pembayaran')
                    ->visible(fn($record) => $record->payments()->count() > 0)
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_number')
                                    ->label('No. Pembayaran')
                                    ->copyable()
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('payment_date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'pending' => 'warning',
                                        'verified' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'pending' => 'Menunggu Verifikasi',
                                        'verified' => 'Terverifikasi',
                                        'rejected' => 'Ditolak',
                                        default => $state,
                                    }),

                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('view')
                                        ->label('Lihat')
                                        ->icon('heroicon-o-eye')
                                        ->url(fn($record) => route('filament.admin.resources.pembayaran.view', $record))
                                        ->openUrlInNewTab(),
                                ]),
                            ])
                            ->columns(4)
                            ->state(fn($record) => $record->payments()->latest()->get()),
                    ]),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('-')
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record->notes),

                Infolists\Components\Section::make('Informasi Tambahan')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('issuedBy.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('issued_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d F Y H:i')
                            ->placeholder('-')
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
