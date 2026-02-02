<?php

namespace App\Filament\Resources;

use App\Models\Bill;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BillPayment;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BillPaymentResource\Pages;

class BillPaymentResource extends Resource
{
    protected static ?string $slug = 'pembayaran';
    protected static ?string $model = BillPayment::class;
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $modelLabel = 'Pembayaran';
    protected static ?string $pluralModelLabel = 'Pembayaran';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('bill.bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paidByUser.residentProfile.full_name')
                    ->label('Dibayar Oleh')
                    ->searchable(['users.name', 'resident_profiles.full_name'])
                    ->sortable()
                    ->default(fn($record) => $record->paid_by_name),

                Tables\Columns\IconColumn::make('is_pic_payment')
                    ->label('PIC')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn($record) => $record->is_pic_payment ? 'Dibayar PIC' : 'Bayar Sendiri'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentMethod.kind')
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

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'kind')
                    ->getOptionLabelFromRecordUsing(fn($record) => match ($record->kind) {
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer Bank',
                        'cash' => 'Tunai',
                        default => $record->kind,
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_pic_payment')
                    ->label('Tipe Pembayaran')
                    ->placeholder('Semua')
                    ->trueLabel('PIC (Gabungan)')
                    ->falseLabel('Individual'),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('receipt')
                    ->label('Cetak Bukti')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn(BillPayment $record) => $record->status === 'verified')
                    ->url(fn(BillPayment $record) => route('receipt.show', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending' && auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Pembayaran')
                    ->modalDescription(fn($record) => "Verifikasi pembayaran sebesar Rp " . number_format($record->amount, 0, ',', '.') . "?")
                    ->action(function (BillPayment $record) {
                        $record->verify(auth()->id());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Pembayaran Diverifikasi')
                            ->body('Pembayaran berhasil diverifikasi dan status tagihan telah diperbarui.')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending' && auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Bukti transfer tidak jelas, nominal tidak sesuai, dll.'),
                    ])
                    ->action(function (BillPayment $record, array $data) {
                        $record->reject($data['rejection_reason'], auth()->id());

                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Pembayaran Ditolak')
                            ->body('Pembayaran telah ditolak.')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pembayaran')
                    ->modalDescription('Apakah Anda yakin ingin menghapus pembayaran ini? Jumlah yang sudah dibayar akan dikembalikan ke tagihan dan transaksi arus kas akan dihapus.')
                    ->before(function (BillPayment $record) {
                        // Kembalikan paid_amount ke bill jika payment sudah verified
                        if ($record->status === 'verified') {
                            DB::transaction(function () use ($record) {
                                // Hapus transaction arus kas
                                $record->deleteTransaction();

                                // Kembalikan ke bill
                                $bill = $record->bill;
                                if ($bill) {
                                    $bill->paid_amount = max(0, $bill->paid_amount - $record->amount);
                                    $bill->remaining_amount = $bill->total_amount - $bill->paid_amount;

                                    // Update status bill
                                    if ($bill->remaining_amount >= $bill->total_amount) {
                                        $bill->status = 'issued';
                                    } elseif ($bill->remaining_amount > 0) {
                                        $bill->status = 'partial';
                                    } else {
                                        $bill->status = 'paid';
                                    }

                                    $bill->save();
                                }
                            });
                        }
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Pembayaran Dihapus')
                            ->body('Pembayaran berhasil dihapus, jumlah dikembalikan ke tagihan, dan transaksi arus kas dihapus.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('verify_bulk')
                    ->label('Verifikasi Terpilih')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Pembayaran Terpilih')
                    ->modalDescription('Verifikasi semua pembayaran yang dipilih?')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $verified = 0;
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $record->verify(auth()->id());
                                $verified++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Berhasil')
                            ->body("{$verified} pembayaran berhasil diverifikasi.")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $query = parent::getEloquentQuery();

        // Super admin & main admin bisa lihat semua
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        // Branch admin: hanya pembayaran dari tagihan di cabangnya
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            return $query->whereHas('bill', function ($q) use ($dormIds) {
                $q->where(function ($subQ) use ($dormIds) {
                    $subQ->whereHas('room.block.dorm', function ($roomQ) use ($dormIds) {
                        $roomQ->whereIn('dorms.id', $dormIds);
                    })
                        ->orWhereHas('user.roomResidents.room.block.dorm', function ($userQ) use ($dormIds) {
                            $userQ->whereIn('dorms.id', $dormIds);
                        });
                });
            });
        }

        // Block admin: hanya pembayaran dari tagihan di bloknya
        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            return $query->whereHas('bill', function ($q) use ($blockIds) {
                $q->where(function ($subQ) use ($blockIds) {
                    $subQ->whereHas('room.block', function ($roomQ) use ($blockIds) {
                        $roomQ->whereIn('blocks.id', $blockIds);
                    })
                        ->orWhereHas('user.roomResidents.room.block', function ($userQ) use ($blockIds) {
                            $userQ->whereIn('blocks.id', $blockIds);
                        });
                });
            });
        }

        // Resident: hanya pembayarannya sendiri
        if ($user->hasRole('resident')) {
            return $query->where('paid_by_user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillPayments::route('/'),
            'create' => Pages\CreateBillPayment::route('/buat'),
            'view' => Pages\ViewBillPayment::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
