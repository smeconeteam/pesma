<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\ResidentCategory;
use App\Models\User;
use App\Models\RoomResident;
use App\Models\Room;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Indicator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Data Penghuni';
    protected static ?string $pluralLabel = 'Data Penghuni';
    protected static ?string $modelLabel = 'Data Penghuni';
    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Hanya super_admin yang bisa lihat data terhapus
        if ($user?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        $query->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
            ->with([
                'residentProfile.residentCategory',
                'roomResidents.room.block.dorm',
            ]);

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin dan main admin: akses semua
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        // Branch admin: hanya penghuni di cabangnya (yang masih aktif penempatan kamar)
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds()->toArray();

            return $query->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                $q->whereNull('check_out_date')
                    ->whereHas('room.block', fn (Builder $b) => $b->whereIn('dorm_id', $dormIds));
            });
        }

        // Block admin: hanya penghuni di kompleknya (yang masih aktif penempatan kamar)
        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();

            return $query->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                $q->whereNull('check_out_date')
                    ->whereHas('room', fn (Builder $room) => $room->whereIn('block_id', $blockIds));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    protected static function getAccessibleDormIds(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;

        // Super admin dan main admin: akses semua
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return null;
        }

        // Branch admin: hanya cabangnya
        if ($user->hasRole('branch_admin')) {
            $ids = $user->branchDormIds()->toArray();
            return !empty($ids) ? $ids : [];
        }

        // Block admin: ambil dorm_id dari block yang dia pegang
        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();
            if (empty($blockIds)) return [];

            $ids = Block::whereIn('id', $blockIds)
                ->pluck('dorm_id')
                ->unique()
                ->values()
                ->all();

            return !empty($ids) ? $ids : [];
        }

        return null;
    }

    protected static function getAccessibleBlockIds(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;

        // Super admin, main admin, branch admin: akses semua block
        if ($user->hasRole(['super_admin', 'main_admin', 'branch_admin'])) {
            return null;
        }

        // Block admin: hanya kompleknya
        if ($user->hasRole('block_admin')) {
            $ids = $user->blockIds()->toArray();
            return !empty($ids) ? $ids : [];
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil Penghuni')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\TextInput::make('profile.full_name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('profile.resident_category_id')
                        ->label('Kategori Penghuni')
                        ->options(fn () => ResidentCategory::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('room.room_id', null);
                            $set('room.is_pic', false);
                        }),

                    Forms\Components\Toggle::make('profile.is_international')
                        ->label('Penghuni Luar Negeri?')
                        ->default(false),

                    Forms\Components\TextInput::make('profile.national_id')
                        ->label('NIK')
                        ->maxLength(16)
                        ->minLength(16)
                        ->rule('digits:16')
                        ->helperText('16 digit, hanya angka.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),

                    Forms\Components\TextInput::make('profile.student_id')
                        ->label('NIM')
                        ->maxLength(50),

                    Forms\Components\Select::make('profile.gender')
                        ->label('Jenis Kelamin')
                        ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                        ->native(false)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('room.room_id', null);
                            $set('room.is_pic', false);
                        }),

                    Forms\Components\TextInput::make('profile.birth_place')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    Forms\Components\DatePicker::make('profile.birth_date')
                        ->label('Tanggal Lahir')
                        ->native(false),

                    Forms\Components\TextInput::make('profile.university_school')
                        ->label('Universitas/Sekolah')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('profile.phone_number')
                        ->label('No. HP')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),

                    Forms\Components\TextInput::make('profile.guardian_name')
                        ->label('Nama Wali')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('profile.guardian_phone_number')
                        ->label('No. HP Wali')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),

                    Forms\Components\FileUpload::make('profile.photo_path')
                        ->label('Foto')
                        ->directory('residents')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Penempatan Kamar')
                ->description('Data ini akan membuat record di room_residents saat disimpan.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('dorm_id')
                        ->label('Cabang (Dorm)')
                        ->options(function () {
                            $ids = static::getAccessibleDormIds();

                            return Dorm::query()
                                ->when(is_array($ids), fn ($q) => $q->whereIn('id', $ids))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('block_id', null);
                            $set('room.room_id', null);
                        }),

                    Forms\Components\Select::make('block_id')
                        ->label('Blok')
                        ->options(function (Forms\Get $get) {
                            $dormId = $get('dorm_id');
                            $blockIds = static::getAccessibleBlockIds();

                            return Block::query()
                                ->when($dormId, fn ($q) => $q->where('dorm_id', $dormId))
                                ->when(is_array($blockIds), fn ($q) => $q->whereIn('id', $blockIds))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->disabled(fn (Forms\Get $get) => blank($get('dorm_id')))
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('room.room_id', null);
                        }),

                    Forms\Components\Select::make('room.room_id')
                        ->label('Kamar')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->disabled(
                            fn (Forms\Get $get) =>
                                blank($get('block_id')) ||
                                blank($get('profile.gender')) ||
                                blank($get('profile.resident_category_id'))
                        )
                        ->helperText(function (Forms\Get $get) {
                            if (blank($get('profile.gender'))) return 'Pilih jenis kelamin dulu.';
                            if (blank($get('profile.resident_category_id'))) return 'Pilih kategori penghuni dulu.';
                            return 'Kamar hanya boleh untuk 1 gender dan 1 kategori penghuni.';
                        })
                        ->options(function (Forms\Get $get) {
                            $blockId    = $get('block_id');
                            $gender     = $get('profile.gender');
                            $categoryId = $get('profile.resident_category_id');

                            if (blank($blockId) || blank($gender) || blank($categoryId)) return [];

                            $rooms = Room::query()
                                ->where('block_id', $blockId)
                                ->where('is_active', true)
                                ->with('residentCategory')
                                ->orderBy('code')
                                ->get();

                            $options = [];

                            foreach ($rooms as $room) {
                                if (!is_null($room->resident_category_id) && (int) $room->resident_category_id !== (int) $categoryId) {
                                    continue;
                                }

                                $activeGender = RoomResident::query()
                                    ->where('room_residents.room_id', $room->id)
                                    ->whereNull('room_residents.check_out_date')
                                    ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                                    ->value('resident_profiles.gender');

                                if ($activeGender && $activeGender !== $gender) {
                                    continue;
                                }

                                $genderLabel = $activeGender
                                    ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                    : 'Kosong';

                                $categoryLabel = $room->residentCategory?->name ?? 'Belum ditentukan';

                                $label = ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                                $label .= " — {$genderLabel} — Kategori: {$categoryLabel} — Kap: {$room->capacity}";

                                $options[$room->id] = $label;
                            }

                            return $options;
                        }),

                    Forms\Components\DatePicker::make('room.check_in_date')
                        ->label('Tanggal Masuk')
                        ->required()
                        ->default(now()->toDateString())
                        ->native(false),

                    Forms\Components\Toggle::make('room.is_pic')
                        ->label('Jadikan PIC?')
                        ->default(false)
                        ->reactive()
                        ->disabled(function (Forms\Get $get) {
                            $roomId = $get('room.room_id');
                            if (blank($roomId)) return true;

                            return RoomResident::query()
                                ->where('room_id', $roomId)
                                ->whereNull('check_out_date')
                                ->where('is_pic', true)
                                ->exists();
                        })
                        ->helperText(function (Forms\Get $get) {
                            $roomId = $get('room.room_id');
                            if (blank($roomId)) return 'Pilih kamar terlebih dahulu.';

                            $hasPic = RoomResident::query()
                                ->where('room_id', $roomId)
                                ->whereNull('check_out_date')
                                ->where('is_pic', true)
                                ->exists();

                            return $hasPic
                                ? 'PIC aktif sudah ada di kamar ini.'
                                : 'Jika diaktifkan, penghuni ini menjadi PIC kamar.';
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residentProfile.full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.residentCategory.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\IconColumn::make('residentProfile.is_international')
                    ->label('LN')
                    ->boolean(),

                Tables\Columns\TextColumn::make('residentProfile.phone_number')
                    ->label('No. HP')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn (Builder $p) => $p->where('gender', $value));
                        });
                    }),

                /**
                 * ✅ FILTER CABANG
                 * - branch_admin: auto isi & dikunci + chip tidak bisa dihapus
                 * - block_admin : auto isi & dikunci + chip tidak bisa dihapus
                 * - kalau cabang berubah => reset blok (super/main)
                 */
                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room.block', fn (Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    })
                    ->form([
                        Select::make('value')
                            ->label('Cabang')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->options(function () {
                                $ids = static::getAccessibleDormIds();

                                return Dorm::query()
                                    ->when(is_array($ids), fn ($q) => $q->whereIn('id', $ids))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user) return null;

                                if ($user->hasRole('branch_admin')) {
                                    return $user->branchDormIds()->first();
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    if (!$blockId) return null;

                                    return Block::whereKey($blockId)->value('dorm_id');
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (Select $component, $state) {
                                $user = auth()->user();
                                if (!$user) return;

                                if (!blank($state)) return;

                                if ($user->hasRole('branch_admin')) {
                                    $component->state($user->branchDormIds()->first());
                                    return;
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    if (!$blockId) return;

                                    $dormId = Block::whereKey($blockId)->value('dorm_id');
                                    $component->state($dormId);
                                }
                            })
                            ->disabled(fn () => auth()->user()?->hasRole(['branch_admin', 'block_admin']) ?? false)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $user = auth()->user();

                                // cabang berubah => reset komplek
                                $set('../block_id.value', null);
                                $set('../../block_id.value', null);

                                // kalau role dikunci dan jadi kosong (karena reset/clear), paksa balik
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
                        // normalize state -> scalar id
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Dorm::query()->whereKey($id)->value('name');
                        if (!$name) return null;

                        $user = auth()->user();
                        $locked = $user?->hasAnyRole(['branch_admin', 'block_admin']) ?? false;

                        return [
                            Indicator::make("Cabang: {$name}")
                                ->removable(! $locked),
                        ];
                    }),

                /**
                 * ✅ FILTER KOMPLEK (DEPENDEN)
                 * - selain block_admin: disable kalau cabang belum dipilih
                 * - block_admin: auto isi & dikunci + chip tidak bisa dihapus
                 */
                SelectFilter::make('block_id')
                    ->label('Komplek')
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $blockId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($blockId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room', fn (Builder $room) => $room->where('block_id', $blockId));
                            });
                        });
                    })
                    ->form([
                        Select::make('value')
                            ->label('Komplek')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->placeholder('Pilih cabang terlebih dahulu')
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user) return null;

                                if ($user->hasRole('block_admin')) {
                                    return $user->blockIds()->first();
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (Select $component, $state) {
                                $user = auth()->user();
                                if (!$user) return;

                                if (!blank($state)) return;

                                if ($user->hasRole('block_admin')) {
                                    $component->state($user->blockIds()->first());
                                }
                            })
                            ->disabled(function (Get $get) {
                                $user = auth()->user();

                                // block_admin dikunci
                                if ($user?->hasRole('block_admin')) {
                                    return true;
                                }

                                // ambil dorm dari sibling filter dorm (fallback beberapa path)
                                $dormState =
                                    $get('../dorm_id.value') ??
                                    $get('../../dorm_id.value') ??
                                    $get('../dorm_id') ??
                                    $get('../../dorm_id');

                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                return blank($dormId);
                            })
                            ->options(function (Get $get) {
                                $user = auth()->user();
                                if (!$user) return [];

                                $dormState =
                                    $get('../dorm_id.value') ??
                                    $get('../../dorm_id.value') ??
                                    $get('../dorm_id') ??
                                    $get('../../dorm_id');

                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                // kalau dorm kosong:
                                // - block_admin tetap dapat opsi scoped (supaya label tampil)
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
                                    if (!in_array((int) $dormId, array_map('intval', $allowedDormIds), true)) {
                                        return [];
                                    }
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('block_admin')) {
                                    return $query->whereIn('id', $user->blockIds())->pluck('name', 'id')->toArray();
                                }

                                return [];
                            })
                            ->helperText(function (Get $get) {
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
                            ->afterStateUpdated(function (Set $set, $state) {
                                $user = auth()->user();

                                // kalau block_admin dan state jadi kosong (karena reset/clear), paksa balik
                                if (($user?->hasRole('block_admin') ?? false) && blank($state)) {
                                    $set('value', $user->blockIds()->first());
                                }
                            }),
                    ])
                    ->indicateUsing(function ($state) {
                        // normalize state -> scalar id
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Block::query()->whereKey($id)->value('name');
                        if (!$name) return null;

                        $user = auth()->user();
                        $locked = $user?->hasRole('block_admin') ?? false;

                        return [
                            Indicator::make("Komplek: {$name}")
                                ->removable(! $locked),
                        ];
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),

                Tables\Actions\EditAction::make()->label('Edit')
                    ->visible(function (User $record) {
                        $user = auth()->user();
                        $allowed = $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']) ?? false;

                        if (!$allowed) {
                            return false;
                        }

                        return !(method_exists($record, 'trashed') && $record->trashed());
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => auth()->user()?->hasRole('super_admin') && $record?->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])),

                Tables\Actions\RestoreBulkAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('super_admin')),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Profil Penghuni')
                ->columns(2)
                ->schema([
                    TextEntry::make('residentProfile.full_name')->label('Nama Lengkap')->placeholder('-'),
                    TextEntry::make('email')->label('Email')->placeholder('-'),
                    IconEntry::make('is_active')->label('Aktif')->boolean(),

                    TextEntry::make('residentProfile.national_id')->label('NIK')->placeholder('-'),
                    TextEntry::make('residentProfile.student_id')->label('NIM')->placeholder('-'),

                    TextEntry::make('residentProfile.gender')->label('Gender')->placeholder('-'),
                    TextEntry::make('residentProfile.phone_number')->label('No. HP')->placeholder('-'),

                    TextEntry::make('residentProfile.residentCategory.name')->label('Kategori')->placeholder('-'),
                    IconEntry::make('residentProfile.is_international')->label('Luar Negeri')->boolean(),
                ]),

            Section::make('Kamar Aktif')
                ->columns(2)
                ->schema([
                    TextEntry::make('kamar_aktif')
                        ->label('Kamar')
                        ->state(function (User $record) {
                            $active = $record->roomResidents()
                                ->whereNull('room_residents.check_out_date')
                                ->latest('check_in_date')
                                ->first();

                            if (!$active?->room) return '-';

                            $room = $active->room;
                            return ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                        }),

                    IconEntry::make('pic_aktif')
                        ->label('PIC?')
                        ->boolean()
                        ->state(function (User $record) {
                            return $record->roomResidents()
                                ->whereNull('room_residents.check_out_date')
                                ->where('room_residents.is_pic', true)
                                ->exists();
                        }),
                ]),
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        $allowed = $user?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']) ?? false;
        if (!$allowed) {
            return false;
        }

        if (method_exists($record, 'trashed') && $record->trashed()) {
            return false;
        }

        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidents::route('/'),
            'edit'  => Pages\EditResident::route('/{record}/edit'),
            'view'  => Pages\ViewResident::route('/{record}'),
        ];
    }
}
