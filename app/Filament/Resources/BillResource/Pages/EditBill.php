<?php

namespace App\Filament\Resources\BillResource\Pages;

use Filament\Actions;
use App\Filament\Resources\BillResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tagihan')
                    ->description('Hanya beberapa field yang dapat diedit')
                    ->schema([
                        Forms\Components\TextInput::make('bill_number')
                            ->label('No. Tagihan')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Placeholder::make('penghuni')
                            ->label('Penghuni')
                            ->content(function ($record) {
                                if (!$record->user) return '-';

                                $fullName = $record->user->residentProfile?->full_name ?? $record->user->name;
                                $phone = $record->user->residentProfile?->phone_number ??$record->user->email;

                                return "{$fullName} ({$phone})";
                            }),

                        Forms\Components\Select::make('billing_type_id')
                            ->label('Jenis Tagihan')
                            ->relationship('billingType', 'name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->relationship('room', 'code')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nominal')
                    ->description('Diskon dapat diubah, nominal lainnya akan otomatis terkalkulasi')
                    ->schema([
                        Forms\Components\Placeholder::make('base_amount_display')
                            ->label('Nominal Dasar')
                            ->content(function ($record) {
                                return 'Rp ' . number_format($record->base_amount ?? 0, 0, ',', '.');
                            }),

                        Forms\Components\Hidden::make('base_amount_original')
                            ->default(fn($record) => $record->base_amount)
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Diskon (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(debounce: 300)
                            ->dehydrateStateUsing(fn($state) => (float) $state)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $baseAmount = (int) ($get('base_amount_original') ?? 0);
                                $discountPercent = (float) ($state ?? 0);

                                $discountAmount = (int) (($baseAmount * $discountPercent) / 100);
                                $totalAmount = $baseAmount - $discountAmount;
                                $paidAmount = (int) ($get('paid_amount') ?? 0);
                                $remainingAmount = max(0, $totalAmount - $paidAmount);

                                $set('discount_amount', $discountAmount);
                                $set('total_amount', $totalAmount);
                                $set('remaining_amount', $remainingAmount);
                            })
                            ->helperText('Edit diskon akan mengubah total tagihan'),

                        Forms\Components\Hidden::make('base_amount_original')
                            ->default(fn($record) => $record->base_amount),

                        Forms\Components\Placeholder::make('discount_amount_display')
                            ->label('Nominal Diskon')
                            ->content(
                                fn(Forms\Get $get) =>
                                'Rp ' . number_format($get('discount_amount') ?? 0, 0, ',', '.')
                            ),

                        Forms\Components\Hidden::make('discount_amount')
                            ->default(fn($record) => $record->discount_amount),

                        Forms\Components\Placeholder::make('total_amount_display')
                            ->label('Total Tagihan')
                            ->content(
                                fn(Forms\Get $get) =>
                                'Rp ' . number_format($get('total_amount') ?? 0, 0, ',', '.')
                            ),

                        Forms\Components\Hidden::make('total_amount')
                            ->default(fn($record) => $record->total_amount),

                        Forms\Components\Placeholder::make('paid_amount_display')
                            ->label('Sudah Dibayar')
                            ->content(
                                fn($record) =>
                                'Rp ' . number_format($record->paid_amount, 0, ',', '.')
                            ),

                        Forms\Components\Hidden::make('paid_amount')
                            ->default(fn($record) => $record->paid_amount),

                        Forms\Components\Placeholder::make('remaining_amount_display')
                            ->label('Sisa Tagihan')
                            ->content(
                                fn(Forms\Get $get) =>
                                'Rp ' . number_format($get('remaining_amount') ?? 0, 0, ',', '.')
                            ),

                        Forms\Components\Hidden::make('remaining_amount')
                            ->default(fn($record) => $record->remaining_amount),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Periode')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Selesai (Jatuh Tempo)')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->nullable()
                            ->minDate(now())
                            ->helperText('Dapat diubah untuk memperpanjang/memperpendek jatuh tempo'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(65535)
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'issued' => 'Tertagih',
                                'partial' => 'Dibayar Sebagian',
                                'paid' => 'Lunas',
                                'overdue' => 'Jatuh Tempo',
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Status otomatis berubah berdasarkan pembayaran'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan semua nilai numeric di-cast dengan benar
        $data['discount_percent'] = (float) ($data['discount_percent'] ?? 0);
        $data['discount_amount'] = (int) ($data['discount_amount'] ?? 0);
        $data['total_amount'] = (int) ($data['total_amount'] ?? 0);
        $data['remaining_amount'] = (int) ($data['remaining_amount'] ?? 0);

        // Update status berdasarkan remaining amount
        if ($data['remaining_amount'] <= 0) {
            $data['status'] = 'paid';
        } elseif ($data['remaining_amount'] < $data['total_amount']) {
            $data['status'] = 'partial';
        } else {
            $data['status'] = 'issued';
        }

        // Cek overdue jika ada period_end
        if (isset($data['period_end']) && $data['period_end']) {
            $periodEnd = \Carbon\Carbon::parse($data['period_end']);
            if ($periodEnd->isPast() && $data['remaining_amount'] > 0) {
                $data['status'] = 'overdue';
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Tagihan berhasil diperbarui')
            ->send();

        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }
}
