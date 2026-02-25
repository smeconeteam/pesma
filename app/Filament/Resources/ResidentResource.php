<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Filament\Resources\RoomPlacementResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\User;

use Filament\Forms\Form;
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Indicator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'data-penghuni';
    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Data Penghuni';
    protected static ?string $pluralLabel = 'Data Penghuni';
    protected static ?string $modelLabel = 'Data Penghuni';
    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        $user  = auth()->user();
        $query = parent::getEloquentQuery();

        // Hanya super_admin yang bisa lihat data terhapus
        if ($user?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        $query->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->with([
                'residentProfile' => function ($q) {
                    $q->withTrashed()->with(['residentCategory']);
                },
                'roomResidents.room.block.dorm',
            ]);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin dan main admin: akses semua
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        // Branch admin: penghuni berkamar di cabangnya + belum berkamar (semua yg belum punya kamar aktif)
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds()->toArray();

            return $query->where(function (Builder $q) use ($dormIds) {
                // 1. Berkamar aktif di cabangnya
                $q->whereHas('roomResidents', function (Builder $rr) use ($dormIds) {
                    $rr->whereNull('check_out_date')
                        ->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                })
                    // 2. Belum punya kamar aktif (baru daftar, pindahan, atau sudah keluar)
                    ->orWhereDoesntHave('roomResidents', fn(Builder $rr) => $rr->whereNull('check_out_date'));
            });
        }

        // Block admin: penghuni di kompleknya + belum berkamar (semua yg belum punya kamar aktif)
        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();

            return $query->where(function (Builder $q) use ($blockIds) {
                // 1. Berkamar aktif di kompleknya
                $q->whereHas('roomResidents', function (Builder $rr) use ($blockIds) {
                    $rr->whereNull('check_out_date')
                        ->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                })
                    // 2. Belum punya kamar aktif (baru daftar, pindahan, atau sudah keluar)
                    ->orWhereDoesntHave('roomResidents', fn(Builder $rr) => $rr->whereNull('check_out_date'));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    // agar halaman view bisa resolve record trashed (mengikuti getEloquentQuery)
    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        return static::getEloquentQuery()
            ->whereKey($key)
            ->first();
    }

    protected static function getAccessibleDormIds(): ?array
    {
        $user = auth()->user();
        if (! $user) return null;

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return null;
        }

        if ($user->hasRole('branch_admin')) {
            $ids = $user->branchDormIds()->toArray();
            return ! empty($ids) ? $ids : [];
        }

        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();
            if (empty($blockIds)) return [];

            $ids = Block::whereIn('id', $blockIds)
                ->pluck('dorm_id')
                ->unique()
                ->values()
                ->all();

            return ! empty($ids) ? $ids : [];
        }

        return null;
    }

    protected static function getAccessibleBlockIds(): ?array
    {
        $user = auth()->user();
        if (! $user) return null;

        if ($user->hasRole(['super_admin', 'main_admin', 'branch_admin'])) {
            return null;
        }

        if ($user->hasRole('block_admin')) {
            $ids = $user->blockIds()->toArray();
            return ! empty($ids) ? $ids : [];
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // fallback jika profile null (data lama)
                Tables\Columns\TextColumn::make('residentProfile.full_name')
                    ->label('Nama')
                    ->getStateUsing(fn(User $record) => $record->residentProfile?->full_name ?? $record->name ?? '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.residentCategory.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.phone_number')
                    ->label('No. HP')
                    ->placeholder('-'),

                // Status berdasarkan kondisi akun dan kamar
                Tables\Columns\TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        // Jika akun nonaktif, langsung return 'inactive'
                        if (!$record->is_active) {
                            return 'inactive';
                        }

                        // Cek apakah pernah punya kamar
                        $hasRoomHistory = $record->roomResidents()->exists();

                        // Cek apakah punya kamar aktif
                        $hasActiveRoom = $record->roomResidents()
                            ->whereNull('check_out_date')
                            ->exists();

                        if (!$hasRoomHistory) {
                            return 'registered'; // Terdaftar tapi belum pernah punya kamar
                        }

                        if ($hasActiveRoom) {
                            return 'active'; // Punya kamar aktif
                        }

                        return 'checkout'; // Sudah pernah punya kamar tapi sekarang keluar
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'registered' => 'warning',
                        'active' => 'success',
                        'checkout' => 'info',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'registered' => 'Terdaftar',
                        'active' => 'Aktif',
                        'checkout' => 'Keluar',
                        'inactive' => 'Nonaktif',
                        default => $state,
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw(
                            "
                            CASE
                                WHEN users.is_active = 0 THEN 4
                                WHEN EXISTS (SELECT 1 FROM room_residents WHERE room_residents.user_id = users.id AND room_residents.check_out_date IS NULL) THEN 1
                                WHEN NOT EXISTS (SELECT 1 FROM room_residents WHERE room_residents.user_id = users.id) THEN 2
                                ELSE 3
                            END " . $direction
                        )->orderBy('created_at', 'desc');
                    }),

                Tables\Columns\TextColumn::make('current_room')
                    ->label('Kamar Aktif')
                    ->getStateUsing(function (User $record) {
                        $active = $record->roomResidents()
                            ->whereNull('check_out_date')
                            ->with('room')
                            ->latest('check_in_date')
                            ->first();

                        if (!$active?->room) return '-';
                        $room = $active->room;
                        return ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                    })
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('gender', $value));
                        });
                    }),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Terdaftar',
                        'active' => 'Aktif',
                        'checkout' => 'Keluar',
                        'inactive' => 'Nonaktif',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        $status = $data['value'] ?? null;

                        if (!$status) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($status) {
                            if ($status === 'inactive') {
                                // Status Nonaktif: is_active = false
                                $q->where('is_active', false);
                            } elseif ($status === 'registered') {
                                // Status Terdaftar: akun aktif DAN belum pernah punya kamar
                                $q->where('is_active', true)
                                    ->whereDoesntHave('roomResidents');
                            } elseif ($status === 'active') {
                                // Status Aktif: akun aktif DAN punya kamar aktif
                                $q->where('is_active', true)
                                    ->whereHas('roomResidents', function (Builder $rr) {
                                        $rr->whereNull('check_out_date');
                                    });
                            } elseif ($status === 'checkout') {
                                // Status Keluar: akun aktif DAN pernah punya kamar tapi sekarang sudah checkout
                                $q->where('is_active', true)
                                    ->whereHas('roomResidents', function (Builder $rr) {
                                        $rr->whereNotNull('check_out_date');
                                    })
                                    ->whereDoesntHave('roomResidents', function (Builder $rr) {
                                        $rr->whereNull('check_out_date');
                                    });
                            }
                        });
                    }),

                /**
                 * FILTER CABANG (HANYA PENGHUNI AKTIF, BUKAN HISTORY)
                 */
                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->label('Cabang')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->options(function () {
                                $ids = static::getAccessibleDormIds();

                                return Dorm::query()
                                    ->when(is_array($ids), fn($q) => $q->whereIn('id', $ids))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default(function () {
                                $user = auth()->user();
                                if (! $user) return null;

                                if ($user->hasRole('branch_admin')) {
                                    return $user->branchDormIds()->first();
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    if (! $blockId) return null;

                                    return Block::whereKey($blockId)->value('dorm_id');
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $state) {
                                $user = auth()->user();
                                if (! $user) return;

                                if (! blank($state)) return;

                                if ($user->hasRole('branch_admin')) {
                                    $component->state($user->branchDormIds()->first());
                                    return;
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    if (! $blockId) return;

                                    $dormId = Block::whereKey($blockId)->value('dorm_id');
                                    $component->state($dormId);
                                }
                            })
                            ->disabled(fn() => auth()->user()?->hasRole(['branch_admin', 'block_admin']) ?? false)
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                $user = auth()->user();

                                $set('../block_id.value', null);
                                $set('../../block_id.value', null);

                                if (($user?->hasRole('branch_admin') ?? false) && blank($state)) {
                                    $set('value', $user->branchDormIds()->first());
                                }

                                if (($user?->hasRole('block_admin') ?? false) && blank($state)) {
                                    $blockId = $user->blockIds()->first();
                                    $set('value', $blockId ? Block::whereKey($blockId)->value('dorm_id') : null);
                                }
                            }),
                    ])
                    ->indicateUsing(function ($state) {
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Dorm::query()->whereKey($id)->value('name');
                        if (! $name) return null;

                        $user   = auth()->user();
                        $locked = $user?->hasAnyRole(['branch_admin', 'block_admin']) ?? false;

                        return [
                            Indicator::make("Cabang: {$name}")
                                ->removable(! $locked),
                        ];
                    }),

                /**
                 * FILTER KOMPLEK (HANYA PENGHUNI AKTIF, BUKAN HISTORY)
                 */
                SelectFilter::make('block_id')
                    ->label('Komplek')
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $blockId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($blockId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room', fn(Builder $room) => $room->where('block_id', $blockId));
                            });
                        });
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->label('Komplek')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->placeholder('Pilih cabang terlebih dahulu')
                            ->default(function () {
                                $user = auth()->user();
                                if (! $user) return null;

                                if ($user->hasRole('block_admin')) {
                                    return $user->blockIds()->first();
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $state) {
                                $user = auth()->user();
                                if (! $user) return;

                                if (! blank($state)) return;

                                if ($user->hasRole('block_admin')) {
                                    $component->state($user->blockIds()->first());
                                }
                            })
                            ->disabled(function (\Filament\Forms\Get $get) {
                                $user = auth()->user();

                                if ($user?->hasRole('block_admin')) {
                                    return true;
                                }

                                $dormState =
                                    $get('../dorm_id.value') ??
                                    $get('../../dorm_id.value') ??
                                    $get('../dorm_id') ??
                                    $get('../../dorm_id');

                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                return blank($dormId);
                            })
                            ->options(function (\Filament\Forms\Get $get) {
                                $user = auth()->user();
                                if (! $user) return [];

                                $dormState =
                                    $get('../dorm_id.value') ??
                                    $get('../../dorm_id.value') ??
                                    $get('../dorm_id') ??
                                    $get('../../dorm_id');

                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                if (blank($dormId)) {
                                    if ($user->hasRole('block_admin')) {
                                        return Block::query()
                                            ->whereNull('deleted_at')
                                            ->whereIn('id', $user->blockIds())
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }

                                    return [];
                                }

                                $query = Block::query()
                                    ->whereNull('deleted_at')
                                    ->where('dorm_id', $dormId)
                                    ->orderBy('name');

                                if ($user->hasRole(['super_admin', 'main_admin'])) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('branch_admin')) {
                                    $allowedDormIds = $user->branchDormIds()->toArray();
                                    if (! in_array((int) $dormId, array_map('intval', $allowedDormIds), true)) {
                                        return [];
                                    }
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('block_admin')) {
                                    return $query->whereIn('id', $user->blockIds())->pluck('name', 'id')->toArray();
                                }

                                return [];
                            })
                            ->helperText(function (\Filament\Forms\Get $get) {
                                $dormState =
                                    $get('../dorm_id.value') ??
                                    $get('../../dorm_id.value') ??
                                    $get('../dorm_id') ??
                                    $get('../../dorm_id');

                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                return blank($dormId)
                                    ? 'Komplek baru bisa dipilih setelah cabang dipilih.'
                                    : null;
                            })
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                $user = auth()->user();

                                if (($user?->hasRole('block_admin') ?? false) && blank($state)) {
                                    $set('value', $user->blockIds()->first());
                                }
                            }),
                    ])
                    ->indicateUsing(function ($state) {
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Block::query()->whereKey($id)->value('name');
                        if (! $name) return null;

                        $user   = auth()->user();
                        $locked = $user?->hasRole('block_admin') ?? false;

                        return [
                            Indicator::make("Komplek: {$name}")
                                ->removable(! $locked),
                        ];
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\ViewAction::make()->label('Lihat'),

                    Tables\Actions\EditAction::make()->label('Edit')
                        ->visible(function (User $record) {
                            $user    = auth()->user();
                            $allowed = $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']) ?? false;

                            if (! $allowed) {
                                return false;
                            }

                            return ! (method_exists($record, 'trashed') && $record->trashed());
                        }),

                    // Tempatkan ke kamar (hanya untuk penghuni belum berkamar)
                    Tables\Actions\Action::make('place')
                        ->label('Tempatkan')
                        ->icon('heroicon-o-map-pin')
                        ->color('success')
                        ->visible(function (User $record) {
                            $user = auth()->user();

                            if (! $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])) {
                                return false;
                            }

                            if (method_exists($record, 'trashed') && $record->trashed()) {
                                return false;
                            }

                            // Hanya tampil jika penghuni belum punya kamar aktif
                            return ! $record->roomResidents()
                                ->whereNull('check_out_date')
                                ->exists();
                        })
                        ->url(fn(User $record) => RoomPlacementResource::getUrl('place', ['record' => $record]))
                        ->openUrlInNewTab(false),

                    // Pindah Kamar / Pindah Cabang
                    Tables\Actions\Action::make('transfer')
                        ->label('Pindah Kamar')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->visible(function (User $record) {
                            $user = auth()->user();

                            if (! $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])) {
                                return false;
                            }

                            if (method_exists($record, 'trashed') && $record->trashed()) {
                                return false;
                            }

                            // Hanya tampil jika penghuni punya kamar aktif
                            $hasActiveRoom = $record->roomResidents()
                                ->whereNull('check_out_date')
                                ->exists();

                            if (! $hasActiveRoom) return false;

                            // Branch admin: hanya bisa memindahkan penghuni dari cabangnya
                            if ($user->hasRole('branch_admin')) {
                                $dormIds = $user->branchDormIds()->toArray();
                                return $record->roomResidents()
                                    ->whereNull('check_out_date')
                                    ->whereHas('room.block', fn($b) => $b->whereIn('dorm_id', $dormIds))
                                    ->exists();
                            }

                            return true;
                        })
                        ->url(fn(User $record) => RoomPlacementResource::getUrl('transfer', ['record' => $record]))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('checkout')
                        ->label('Keluar')
                        ->icon('heroicon-o-arrow-left-on-rectangle')
                        ->color('warning')
                        ->visible(function (User $record) {
                            $user = auth()->user();

                            // Super admin, main admin, dan branch admin bisa checkout
                            if (!$user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])) {
                                return false;
                            }

                            // Tidak bisa checkout jika sudah soft deleted
                            if (method_exists($record, 'trashed') && $record->trashed()) {
                                return false;
                            }

                            // Hanya tampil jika penghuni memiliki kamar aktif
                            $hasActiveRoom = $record->roomResidents()
                                ->whereNull('check_out_date')
                                ->exists();

                            if (! $hasActiveRoom) return false;

                            // Branch admin: hanya bisa checkout penghuni dari cabangnya
                            if ($user->hasRole('branch_admin')) {
                                $dormIds = $user->branchDormIds()->toArray();
                                return $record->roomResidents()
                                    ->whereNull('check_out_date')
                                    ->whereHas('room.block', fn($b) => $b->whereIn('dorm_id', $dormIds))
                                    ->exists();
                            }

                            return true;
                        })
                        ->url(fn(User $record) => RoomPlacementResource::getUrl('checkout', ['record' => $record]))
                        ->openUrlInNewTab(false),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(function (User $record) {
                            $user = auth()->user();
                            // Hanya super_admin dan main_admin yang bisa hapus
                            if (! $user?->hasAnyRole(['super_admin', 'main_admin'])) {
                                return false;
                            }

                            // Hanya tampil di tab selain terhapus
                            if (method_exists($record, 'trashed') && $record->trashed()) {
                                return false;
                            }

                            return true;
                        })
                        ->before(function (Tables\Actions\DeleteAction $action, User $record) {
                            // Cek status akun
                            if ($record->is_active) {
                                // Tentukan status penghuni untuk pesan yang lebih spesifik
                                $hasRoomHistory = $record->roomResidents()->exists();
                                $hasActiveRoom = $record->roomResidents()
                                    ->whereNull('check_out_date')
                                    ->exists();

                                // Tentukan status dan pesan
                                if (!$hasRoomHistory) {
                                    // Status Terdaftar
                                    $message = "Penghuni dengan status Terdaftar tidak dapat dihapus. Nonaktifkan terlebih dahulu.";
                                } elseif ($hasActiveRoom) {
                                    // Status Aktif
                                    $message = "Penghuni dengan status Aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.";
                                } else {
                                    // Status Keluar
                                    $message = "Penghuni dengan status Keluar tidak dapat dihapus. Nonaktifkan terlebih dahulu.";
                                }

                                Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat menghapus')
                                    ->body($message)
                                    ->send();

                                $action->cancel();
                            }
                        }),

                    Tables\Actions\ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->visible(function (User $record) {
                            $user = auth()->user();
                            // Hanya super_admin yang bisa hapus permanen
                            if (! $user?->hasRole('super_admin')) {
                                return false;
                            }

                            // Hanya tampil di tab terhapus
                            return method_exists($record, 'trashed') && $record->trashed();
                        }),

                    Tables\Actions\RestoreAction::make()
                        ->label('Pulihkan')
                        ->visible(function (User $record) {
                            $user = auth()->user();
                            // Hanya super_admin yang bisa pulihkan
                            if (! $user?->hasRole('super_admin')) {
                                return false;
                            }

                            // Hanya tampil di tab terhapus
                            return method_exists($record, 'trashed') && $record->trashed();
                        }),
                ]),
            ])
            ->defaultSort(
                fn(Builder $query) =>
                $query->orderByRaw("
                    CASE
                        WHEN users.is_active = 0 THEN 4
                        WHEN EXISTS (SELECT 1 FROM room_residents WHERE room_residents.user_id = users.id AND room_residents.check_out_date IS NULL) THEN 1
                        WHEN NOT EXISTS (SELECT 1 FROM room_residents WHERE room_residents.user_id = users.id) THEN 2
                        ELSE 3
                    END ASC
                ")->orderBy('created_at', 'desc')
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidents::route('/'),
            'create' => Pages\CreateResident::route('/buat'),
            'edit' => Pages\EditResident::route('/{record}/edit'),
            'view' => Pages\ViewResident::route('/{record}'),
        ];
    }
}
