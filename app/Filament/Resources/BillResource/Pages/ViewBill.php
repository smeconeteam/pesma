<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Tagihan')
                    ->schema([
                        TextEntry::make('bill_number')->label('Nomor Tagihan'),
                        TextEntry::make('user.name')->label('Penghuni'),
                        TextEntry::make('bill_type')
                            ->label('Tipe')
                            ->formatStateUsing(fn($state) => match($state) {
                                'registration' => 'Pendaftaran',
                                'monthly_room' => 'Kamar Bulanan',
                                'custom' => 'Kustom',
                                default => $state,
                            }),
                        TextEntry::make('room.code')->label('Kamar')->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn($state) => match($state) {
                                'pending' => 'Menunggu',
                                'partial' => 'Sebagian',
                                'paid' => 'Lunas',
                                'overdue' => 'Terlambat',
                                default => $state,
                            })
                            ->color(fn($state) => match($state) {
                                'pending' => 'warning',
                                'partial' => 'info',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('due_date')
                            ->label('Jatuh Tempo')
                            ->date('d M Y')
                            ->placeholder('Unlimited'),
                    ])
                    ->columns(3),

                Section::make('Rincian Biaya')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('description')->label('Deskripsi'),
                                TextEntry::make('amount')
                                    ->label('Nominal')
                                    ->money('IDR', true),
                                TextEntry::make('quantity')->label('Qty'),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->state(fn($record) => $record->amount * $record->quantity)
                                    ->money('IDR', true),
                            ])
                            ->columns(4),

                        TextEntry::make('total_amount')
                            ->label('Subtotal')
                            ->money('IDR', true),
                        TextEntry::make('discount_amount')
                            ->label('Diskon')
                            ->money('IDR', true)
                            ->color('success'),
                        TextEntry::make('final_amount')
                            ->label('Total Akhir')
                            ->money('IDR', true)
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->money('IDR', true)
                            ->color('info'),
                        TextEntry::make('remaining_amount')
                            ->label('Sisa')
                            ->state(fn($record) => max(0, $record->final_amount - $record->paid_amount))
                            ->money('IDR', true)
                            ->color('warning')
                            ->weight('bold'),
                    ])
                    ->columns(3),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')->label('')->placeholder('-'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}