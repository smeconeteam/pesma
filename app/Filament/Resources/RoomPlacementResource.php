<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomPlacementResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoomPlacementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'penempatan-kamar';
    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Penempatan Kamar';
    protected static ?string $pluralLabel = 'Penempatan Kamar';
    protected static ?string $modelLabel = 'Penempatan Kamar';
    protected static ?int $navigationSort = 31;

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']) ?? false;
    }

    /**
     * Scope untuk menampilkan penghuni yang RELEVAN bagi branch_admin:
     *
     * 1. Penghuni yang SEDANG berkamar di cabangnya       → bisa transfer / checkout
     * 2. Penghuni yang BELUM berkamar sama sekali          → bisa ditempatkan ke cabangnya
     * 3. Penghuni yang SUDAH KELUAR dari cabangnya         → ditampilkan sebagai riwayat
     *
     * Penghuni yang berkamar di cabang LAIN tidak ditampilkan.
     */
    public static function applyBranchScope(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && $user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds()->toArray();

            return $query->where(function ($q) use ($dormIds) {
                // 1. Sedang berkamar aktif di cabang ini
                $q->whereHas('activeRoomResident.room.block', function ($sub) use ($dormIds) {
                    $sub->whereIn('dorm_id', $dormIds);
                })
                    // 2. Belum pernah punya kamar sama sekali (penghuni baru)
                    ->orWhere(function ($sub) {
                        $sub->whereDoesntHave('roomResidents');
                    })
                    // 3. Sudah keluar tapi kamar terakhirnya di cabang ini
                    ->orWhere(function ($sub) use ($dormIds) {
                        $sub->whereDoesntHave('activeRoomResident')
                            ->whereHas('roomResidents', function ($rr) use ($dormIds) {
                                // hanya jika ada minimal satu record di cabang ini
                                $rr->whereHas('room.block', fn($b) => $b->whereIn('dorm_id', $dormIds));
                            });
                    });
            });
        }

        return $query;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas(
                'residentProfile',
                fn(Builder $q) => $q->whereIn('status', ['registered', 'active'])
            )
            ->with([
                'residentProfile.residentCategory',
                'residentProfile.country',
                'activeRoomResident.room.block.dorm',
            ]);

        return static::applyBranchScope($query);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residentProfile.full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('residentProfile.status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'registered',
                        'success' => 'active',
                        'danger'  => 'inactive',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'registered' => 'Terdaftar',
                        'active'     => 'Aktif',
                        'inactive'   => 'Nonaktif',
                        default      => '-',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.gender')
                    ->label('Gender')
                    ->formatStateUsing(fn($state) => $state === 'M' ? 'Laki-laki' : 'Perempuan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_room')
                    ->label('Kamar Saat Ini')
                    ->state(function (User $record) {
                        $active = $record->activeRoomResident;
                        if (! $active) return '-';

                        $room  = $active->room;
                        $block = $room->block;
                        $dorm  = $block->dorm;

                        return "{$dorm->name} - {$block->name} - {$room->number}";
                    }),

                Tables\Columns\IconColumn::make('has_room')
                    ->label('Ada Kamar?')
                    ->boolean()
                    ->state(fn(User $record) => $record->activeRoomResident !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Terdaftar',
                        'active'     => 'Aktif',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $status) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('status', $status));
                        });
                    })
                    ->native(false),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'M' => 'Laki-laki',
                        'F' => 'Perempuan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $gender) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('gender', $gender));
                        });
                    })
                    ->native(false),

                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->where(function ($sub) use ($dormId) {
                                $sub->whereHas('activeRoomResident.room.block', fn($b) => $b->where('dorm_id', $dormId))
                                    ->orWhere(function ($sub2) use ($dormId) {
                                        $sub2->whereDoesntHave('activeRoomResident')
                                            ->whereHas('roomResidents.room.block', fn($b) => $b->where('dorm_id', $dormId));
                                    });
                            });
                        });
                    })
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'main_admin']))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('place')
                    ->label('Tempatkan')
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->visible(fn(User $record) => $record->activeRoomResident === null)
                    ->url(fn(User $record) => static::getUrl('place', ['record' => $record])),

                Tables\Actions\Action::make('transfer')
                    ->label('Pindah Kamar')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn(User $record) => $record->activeRoomResident !== null)
                    ->url(fn(User $record) => static::getUrl('transfer', ['record' => $record])),

                Tables\Actions\Action::make('checkout')
                    ->label('Keluar')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->activeRoomResident !== null)
                    ->url(fn(User $record) => static::getUrl('checkout', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListRoomPlacements::route('/'),
            'place'    => Pages\PlaceResident::route('/{record}/tempatkan'),
            'transfer' => Pages\TransferResident::route('/{record}/pindah'),
            'checkout' => Pages\CheckoutResident::route('/{record}/keluar'),
        ];
    }
}
