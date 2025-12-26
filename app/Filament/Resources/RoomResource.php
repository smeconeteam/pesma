<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\ResidentCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Select;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Kamar Asrama';
    protected static ?string $modelLabel = 'Kamar Asrama';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kamar')
                    ->schema([
                        Select::make('dorm_id')
                            ->label('Cabang')
                            ->dehydrated(false)
                            ->options(function (?Room $record) {
                                $query = Dorm::query()->orderBy('name');

                                // ✅ Saat EDIT: tampilkan yang aktif + yang sudah terpilih
                                if ($record && $record->exists && $record->block?->dorm_id) {
                                    $query->where(function ($q) use ($record) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $record->block->dorm_id);
                                    });
                                } else {
                                    // ✅ Saat CREATE: hanya yang aktif
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                if ($record?->block?->dorm_id) {
                                    $component->state($record->block->dorm_id);
                                }
                            })
                            ->afterStateUpdated(function (Set $set) {
                                $set('block_id', null);
                                $set('code', null);
                            })
                            ->disabled(function ($record) {
                                if (!$record) return false;

                                return RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                            })
                            ->helperText(function ($record) {
                                if (!$record) {
                                    return 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.';
                                }

                                $hasActiveResidents = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();

                                return $hasActiveResidents
                                    ? 'Cabang tidak dapat diubah karena kamar ini masih memiliki penghuni aktif.'
                                    : 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.';
                            }),

                        Select::make('block_id')
                            ->label('Komplek')
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
                            ->options(function (Get $get, ?Room $record) {
                                $dormId = $get('dorm_id');
                                if (!$dormId) {
                                    return [];
                                }

                                $query = Block::query()
                                    ->where('dorm_id', $dormId)
                                    ->orderBy('name');

                                // ✅ Saat EDIT: tampilkan yang aktif + yang sudah terpilih
                                if ($record && $record->exists && $record->block_id) {
                                    $query->where(function ($q) use ($record) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $record->block_id);
                                    });
                                } else {
                                    // ✅ Saat CREATE: hanya yang aktif
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->disabled(function (Get $get, $record) {
                                if (blank($get('dorm_id'))) return true;

                                if (!$record) return false;

                                return RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                            })
                            ->helperText(function (Get $get, $record) {
                                if (blank($get('dorm_id'))) {
                                    return 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.';
                                }

                                if (!$record) return null;

                                $hasActiveResidents = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();

                                return $hasActiveResidents
                                    ? 'Komplek tidak dapat diubah karena kamar ini masih memiliki penghuni aktif.'
                                    : null;
                            }),

                        Select::make('room_type_id')
                            ->label('Tipe Kamar')
                            ->options(function (?Room $record) {
                                $query = RoomType::query()->orderBy('name');

                                // ✅ Saat EDIT: tampilkan yang aktif + yang sudah terpilih
                                if ($record && $record->exists && $record->room_type_id) {
                                    $query->where(function ($q) use ($record) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $record->room_type_id);
                                    });
                                } else {
                                    // ✅ Saat CREATE: hanya yang aktif
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get)),

                        Forms\Components\TextInput::make('number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->maxLength(20)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
                            ->helperText('Contoh: 01, 02, 101, dst.'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kamar')
                            ->disabled()
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->dehydrated(true)
                            ->readonly(),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Kapasitas')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Boleh kosong, nanti bisa mengikuti kapasitas default dari tipe kamar.'),

                        Forms\Components\TextInput::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefix('Rp')
                            ->helperText('Boleh kosong, nanti bisa mengikuti tarif default dari tipe kamar.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Select::make('resident_category_id')
                            ->label('Kategori Kamar')
                            ->relationship('residentCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Kategori hanya bisa diubah jika kamar kosong (tidak ada penghuni aktif).')
                            ->disabled(function (?Room $record) {
                                if (!$record) return false;
                                return !$record->isEmpty();
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('block.dorm.name')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('block.name')
                    ->label('Komplek')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Tipe Kamar')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),

                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(function () {
                        $user = auth()->user();

                        if (!$user) return [];

                        $query = Dorm::query()
                            ->whereNull('deleted_at')
                            ->orderBy('name');

                        // Super admin & main admin: semua cabang
                        if ($user->hasRole(['super_admin', 'main_admin'])) {
                            return $query->pluck('name', 'id')->toArray();
                        }

                        // Branch admin: hanya cabangnya
                        if ($user->hasRole('branch_admin')) {
                            return $query->whereIn('id', $user->branchDormIds())->pluck('name', 'id')->toArray();
                        }

                        // Block admin: cabang dari kompleknya
                        if ($user->hasRole('block_admin')) {
                            $blockIds = $user->blockIds()->toArray();
                            $dormIds = Block::whereIn('id', $blockIds)->pluck('dorm_id')->unique()->all();
                            return $query->whereIn('id', $dormIds)->pluck('name', 'id')->toArray();
                        }

                        return [];
                    })
                    ->searchable()
                    ->native(false)
                    ->default(function () {
                        $user = auth()->user();

                        // Auto-set untuk branch admin (1 cabang)
                        if ($user?->hasRole('branch_admin')) {
                            $dormIds = $user->branchDormIds()->toArray();
                            return count($dormIds) === 1 ? $dormIds[0] : null;
                        }

                        // Auto-set untuk block admin (ambil dorm dari block)
                        if ($user?->hasRole('block_admin')) {
                            $blockIds = $user->blockIds()->toArray();
                            if (!empty($blockIds)) {
                                $dormIds = Block::whereIn('id', $blockIds)
                                    ->pluck('dorm_id')
                                    ->unique()
                                    ->values()
                                    ->all();
                                return count($dormIds) === 1 ? $dormIds[0] : null;
                            }
                        }

                        return null;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn(Builder $q, $dormId) => $q->whereHas('block', fn(Builder $qb) => $qb->where('dorm_id', $dormId))
                        );
                    }),

                SelectFilter::make('block_id')
                    ->label('Komplek')
                    ->options(function () {
                        $user = auth()->user();

                        if (!$user) return [];

                        $query = Block::query()
                            ->whereNull('deleted_at')
                            ->orderBy('name');

                        // Super admin, main admin: semua komplek
                        if ($user->hasRole(['super_admin', 'main_admin'])) {
                            return $query->pluck('name', 'id')->toArray();
                        }

                        // Branch admin: komplek di cabangnya
                        if ($user->hasRole('branch_admin')) {
                            $dormIds = $user->branchDormIds()->toArray();
                            return $query->whereIn('dorm_id', $dormIds)->pluck('name', 'id')->toArray();
                        }

                        // Block admin: hanya kompleknya
                        if ($user->hasRole('block_admin')) {
                            return $query->whereIn('id', $user->blockIds())->pluck('name', 'id')->toArray();
                        }

                        return [];
                    })
                    ->searchable()
                    ->native(false)
                    ->default(function () {
                        $user = auth()->user();

                        // Auto-set untuk block admin (1 komplek)
                        if ($user?->hasRole('block_admin')) {
                            $blockIds = $user->blockIds()->toArray();
                            return count($blockIds) === 1 ? $blockIds[0] : null;
                        }

                        return null;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, fn(Builder $q, $blockId) => $q->where('block_id', $blockId));
                    }),

                SelectFilter::make('room_type_id')
                    ->label('Tipe Kamar')
                    ->options(fn() => RoomType::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, fn(Builder $q, $typeId) => $q->where('room_type_id', $typeId));
                    }),

                TernaryFilter::make('is_empty')
                    ->label('Status Penghuni')
                    ->placeholder('Semua Kamar')
                    ->trueLabel('Kamar Kosong')
                    ->falseLabel('Kamar Terisi')
                    ->native(false)
                    ->queries(
                        true: fn(Builder $query) => $query->whereDoesntHave('roomResidents', function (Builder $q) {
                            $q->whereNull('check_out_date');
                        }),
                        false: fn(Builder $query) => $query->whereHas('roomResidents', function (Builder $q) {
                            $q->whereNull('check_out_date');
                        }),
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(function (Room $record): bool {
                        $user = auth()->user();

                        if (!($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
                            return false;
                        }

                        if ($record->trashed()) {
                            return false;
                        }

                        return !RoomResident::query()
                            ->where('room_id', $record->id)
                            ->whereNull('check_out_date')
                            ->exists();
                    }),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn(Room $record): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin']))
                        ->action(function (Collection $records) {
                            $allowed = $records->filter(function (Room $room) {
                                return !RoomResident::query()
                                    ->where('room_id', $room->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                            });

                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada kamar yang bisa dihapus. Kamar yang masih memiliki penghuni aktif tidak dapat dihapus.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            foreach ($allowed as $room) {
                                $room->delete();
                            }

                            $deleted = $allowed->count();

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Berhasil Sebagian')
                                    ->body("Berhasil menghapus {$deleted} kamar. Yang tidak bisa dihapus: " . $blocked->pluck('code')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} kamar.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Lokasi')
                    ->schema([
                        TextEntry::make('block.dorm.name')->label('Cabang')->placeholder('-'),
                        TextEntry::make('block.name')->label('Komplek')->placeholder('-'),
                        TextEntry::make('roomType.name')->label('Tipe Kamar')->placeholder('-'),
                    ])
                    ->columns(3),

                InfoSection::make('Detail Kamar')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Kamar')
                            ->copyable()
                            ->copyMessage('Kode disalin'),

                        TextEntry::make('number')->label('Nomor Kamar')->placeholder('-'),

                        TextEntry::make('capacity')
                            ->label('Kapasitas')
                            ->formatStateUsing(fn($state) => $state ? "{$state} orang" : '-'),

                        TextEntry::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->money('IDR', true),

                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),

                        TextEntry::make('penghuni_aktif')
                            ->label('Penghuni Aktif')
                            ->state(
                                fn(Room $record) => RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->count()
                            )
                            ->suffix(' orang'),
                    ])
                    ->columns(3),

                InfoSection::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diubah')->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereHas('block.dorm');

        // Hanya super_admin yang bisa lihat data terhapus
        if ($user?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin & main admin: akses semua
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        // Branch admin: filter hanya kamar di cabangnya
        if ($user->hasRole('branch_admin')) {
            return $query->whereHas(
                'block',
                fn(Builder $q) => $q->whereIn('dorm_id', $user->branchDormIds())
            );
        }

        // Block admin: filter hanya kamar di kompleknya
        if ($user->hasRole('block_admin')) {
            return $query->whereIn('block_id', $user->blockIds());
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole([
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
        ]) ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit'   => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    protected static function generateRoomCode(Set $set, Get $get): void
    {
        $dormId     = $get('dorm_id');
        $blockId    = $get('block_id');
        $roomTypeId = $get('room_type_id');
        $number     = $get('number');

        if (!$dormId || !$blockId || !$roomTypeId || !$number) {
            $set('code', null);
            return;
        }

        $dorm     = Dorm::find($dormId);
        $block    = Block::find($blockId);
        $roomType = RoomType::find($roomTypeId);

        if (!$dorm || !$block || !$roomType) {
            $set('code', null);
            return;
        }

        $code = Room::generateCode(
            $dorm->name,
            $block->name,
            $roomType->name,
            (string) $number
        );

        $set('code', strtolower($code));
    }
}
