<?php

namespace App\Filament\Resources;

use App\Models\Bill;
use App\Models\Dorm;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BillResource\Pages;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillResource extends Resource
{
    protected static ?string $slug = 'tagihan';
    protected static ?string $model = Bill::class;
    protected static ?string $navigationLabel = 'Tagihan';
    protected static ?string $modelLabel = 'Tagihan';
    protected static ?string $pluralModelLabel = 'Tagihan';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.residentProfile.full_name')
                    ->label('Penghuni')
                    ->searchable(['users.name', 'resident_profiles.full_name'])
                    ->sortable()
                    ->default('Belum terdaftar'),

                Tables\Columns\TextColumn::make('billingType.name')
                    ->label('Jenis')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('room.code')
                    ->label('Kamar')
                    ->sortable()
                    ->default('Belum punya kamar')
                    ->formatStateUsing(fn($state) => $state ?? 'Belum punya kamar'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'issued',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn($record) => $record->status_label),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'issued' => 'Tertagih',
                        'partial' => 'Dibayar Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('billing_type_id')
                    ->label('Jenis Tagihan')
                    ->relationship('billingType', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->hasRole(['super_admin', 'main_admin'])) {
                            return Dorm::where('is_active', true)
                                ->pluck('name', 'id');
                        }

                        if ($user->hasRole('branch_admin')) {
                            return Dorm::whereIn('id', $user->branchDormIds())
                                ->pluck('name', 'id');
                        }

                        if ($user->hasRole('block_admin')) {
                            $dormIds = \App\Models\Block::whereIn('id', $user->blockIds())
                                ->pluck('dorm_id')
                                ->unique();

                            return Dorm::whereIn('id', $dormIds)
                                ->pluck('name', 'id');
                        }

                        return [];
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where(function ($q) use ($data) {
                                $q->whereHas('room.block.dorm', function ($subQ) use ($data) {
                                    $subQ->whereIn('dorms.id', $data['value']);
                                })
                                    ->orWhereHas('user.roomResidents.room.block.dorm', function ($subQ) use ($data) {
                                        $subQ->whereIn('dorms.id', $data['value']);
                                    });
                            });
                        }
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Jatuh Tempo')
                    ->placeholder('Semua')
                    ->trueLabel('Ya (Lewat Jatuh Tempo)')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('period_end')
                            ->where('period_end', '<', now())
                            ->whereIn('status', ['issued', 'partial', 'overdue']),
                        false: fn(Builder $query) => $query->where(function ($q) {
                            $q->where('period_end', '>=', now())
                                ->orWhereNull('period_end')
                                ->orWhere('status', 'paid');
                        }),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn($record) => !$record->trashed()),

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => !$record->trashed()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => !$record->trashed() && $record->canBeDeleted()),

                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(
                        fn(Bill $record): bool =>
                        auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    )
                    ->action(function (Bill $record) {
                        $registrationBillingType = \App\Models\BillingType::where('name', 'Biaya Pendaftaran')->first();

                        if ($registrationBillingType && $record->billing_type_id === $registrationBillingType->id) {
                            $existingBill = Bill::withoutTrashed()
                                ->where('user_id', $record->user_id)
                                ->where('billing_type_id', $registrationBillingType->id)
                                ->where('id', '!=', $record->id)
                                ->exists();

                            if ($existingBill) {
                                Notification::make()
                                    ->title('Gagal Memulihkan')
                                    ->body('Pengguna ini sudah memiliki tagihan pendaftaran aktif. Tidak dapat memulihkan tagihan ini.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        $record->restore();

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Tagihan berhasil dipulihkan.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn(Bill $record): bool =>
                        auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Tagihan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen tagihan ini? Data yang dihapus permanen tidak dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus')
                    ->visible(function ($livewire) {
                        return ($livewire->activeTab ?? null) !== 'terhapus';
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->label('Pulihkan')
                    ->visible(function ($livewire) {
                        $user = auth()->user();

                        if (!$user?->hasRole(['super_admin'])) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) === 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $registrationBillingType = \App\Models\BillingType::where('name', 'Biaya Pendaftaran')->first();
                        $restorable = collect();
                        $blocked = collect();

                        foreach ($records as $record) {
                            if (!method_exists($record, 'trashed') || !$record->trashed()) {
                                continue;
                            }

                            if ($registrationBillingType && $record->billing_type_id === $registrationBillingType->id) {
                                $existingBill = Bill::withoutTrashed()
                                    ->where('user_id', $record->user_id)
                                    ->where('billing_type_id', $registrationBillingType->id)
                                    ->where('id', '!=', $record->id)
                                    ->exists();

                                if ($existingBill) {
                                    $blocked->push($record);
                                    continue;
                                }
                            }

                            $restorable->push($record);
                        }

                        if ($restorable->isEmpty()) {
                            Notification::make()
                                ->title('Tidak Bisa Dipulihkan')
                                ->body('Semua tagihan yang dipilih tidak dapat dipulihkan karena pengguna sudah memiliki tagihan pendaftaran aktif.')
                                ->danger()
                                ->send();
                            return;
                        }

                        foreach ($restorable as $r) {
                            $r->restore();
                        }

                        $restoredCount = $restorable->count();

                        if ($blocked->isNotEmpty()) {
                            $blockedUsers = $blocked->map(fn($r) => $r->user->residentProfile->full_name ?? $r->user->name ?? 'Unknown')->unique()->take(5)->join(', ');
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil memulihkan {$restoredCount} tagihan. Yang tidak bisa dipulihkan: {$blockedUsers}" . ($blocked->count() > 5 ? '...' : ''))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil memulihkan {$restoredCount} tagihan.")
                                ->success()
                                ->send();
                        }
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

        // Super admin bisa lihat data terhapus
        if ($user->hasRole('super_admin')) {
            $query = $query->withoutGlobalScopes([
                SoftDeletingScope::class
            ]);
        }

        // Role-based filtering
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

    if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            return $query->where(function ($q) use ($dormIds) {
                $q->whereHas('room.block.dorm', function ($subQ) use ($dormIds) {
                    $subQ->whereIn('dorms.id', $dormIds);
                })
                    ->orWhereHas('user.roomResidents.room.block.dorm', function ($subQ) use ($dormIds) {
                        $subQ->whereIn('dorms.id', $dormIds);
                    });
            });
        }

        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            return $query->where(function ($q) use ($blockIds) {
                $q->whereHas('room.block', function ($subQ) use ($blockIds) {
                    $subQ->whereIn('blocks.id', $blockIds);
                })
                    ->orWhereHas('user.roomResidents.room.block', function ($subQ) use ($blockIds) {
                        $subQ->whereIn('blocks.id', $blockIds);
                    });
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/buat'),
            'view' => Pages\ViewBill::route('/{record}'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['issued', 'partial', 'overdue'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
