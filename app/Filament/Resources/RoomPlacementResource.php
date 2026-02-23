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

    public static function applyBranchScope(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user && $user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            return $query->where(function ($q) use ($dormIds) {
                // 1. Sedang punya kamar aktif di cabang ini
                $q->whereHas('activeRoomResident.room.block', function ($subQ) use ($dormIds) {
                    $subQ->whereIn('dorm_id', $dormIds);
                })
                // 2. ATAU sudah keluar (tidak punya kamar aktif sama sekali), tapi kamar terakhirnya di cabang ini
                ->orWhere(function ($subQ) use ($dormIds) {
                    $subQ->whereDoesntHave('activeRoomResident')
                         ->whereHas('roomResidents.room.block', function ($subSubQ) use ($dormIds) {
                             $subSubQ->whereIn('dorm_id', $dormIds);
                         });
                })
                // 3. ATAU belum pernah punya kamar di manapun, tapi pendaftarannya memilih cabang ini ATAU belum memilih cabang (tanpa preferensi)
                ->orWhere(function ($subQ) use ($dormIds) {
                    $subQ->whereDoesntHave('roomResidents')
                         ->whereHas('registrations', function ($regQ) use ($dormIds) {
                            $regQ->where('status', 'approved')
                                 ->where(function ($q) use ($dormIds) {
                                     $q->whereIn('preferred_dorm_id', $dormIds)
                                       ->orWhereNull('preferred_dorm_id');
                                 });
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
                fn(Builder $q) =>
                $q->whereIn('status', ['registered', 'active'])
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
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'registered' => 'Terdaftar',
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => '-',
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
                        if (!$active) return '-';

                        $room = $active->room;
                        $block = $room->block;
                        $dorm = $block->dorm;

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
                        'active' => 'Aktif',
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
                    ->options(fn () => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->where(function ($subQ) use ($dormId) {
                                // 1. Aktif di kamar cabang ini
                                $subQ->whereHas('activeRoomResident.room.block', function ($subSubQ) use ($dormId) {
                                    $subSubQ->where('dorm_id', $dormId);
                                })
                                // 2. ATAU sudah keluar (tidak punya kamar aktif di cabang mana pun), tapi pernah di cabang ini
                                ->orWhere(function ($subSubQ) use ($dormId) {
                                    $subSubQ->whereDoesntHave('activeRoomResident')
                                            ->whereHas('roomResidents.room.block', function ($subSubSubQ) use ($dormId) {
                                                $subSubSubQ->where('dorm_id', $dormId);
                                            });
                                })
                                // 3. ATAU belum pernah punya kamar, dan saat daftar milih cabang ini ATAU belum memilih cabang
                                ->orWhere(function ($subSubQ) use ($dormId) {
                                    $subSubQ->whereDoesntHave('roomResidents')
                                            ->whereHas('registrations', function ($regQ) use ($dormId) {
                                                $regQ->where('status', 'approved')
                                                     ->where(function ($q) use ($dormId) {
                                                         $q->where('preferred_dorm_id', $dormId)
                                                           ->orWhereNull('preferred_dorm_id');
                                                     });
                                            });
                                });
                            });
                        });
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'main_admin']))
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
