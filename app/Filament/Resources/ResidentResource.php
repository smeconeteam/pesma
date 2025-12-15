<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\RoomResident;
use App\Models\ResidentCategory;
use App\Models\Room;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Penghuni';
    protected static ?string $pluralLabel = 'Penghuni';
    protected static ?string $modelLabel = 'Penghuni';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
            ->with([
                'residentProfile.residentCategory',
                'roomResidents.room',
            ]);
    }

    /**
     * OPTIONAL: Batasi dorm/block sesuai scope admin.
     * Sesuaikan relasi sesuai project kamu (misal: $user->adminScopes()).
     */
    protected static function getAccessibleDormIds(): ?array
    {
        $u = auth()->user();

        if (! $u) return null;
        if ($u->hasRole('super_admin') || $u->hasRole('main_admin')) return null;

        // ⚠️ Sesuaikan kalau relasi kamu beda nama
        if (method_exists($u, 'adminScopes')) {
            $ids = $u->adminScopes()
                ->whereNotNull('dorm_id')
                ->pluck('dorm_id')
                ->unique()
                ->values()
                ->all();

            return count($ids) ? $ids : [];
        }

        return null; // kalau belum ada relasi, jangan filter
    }

    protected static function getAccessibleBlockIds(): ?array
    {
        $u = auth()->user();

        if (! $u) return null;
        if ($u->hasRole('super_admin') || $u->hasRole('main_admin') || $u->hasRole('branch_admin')) return null;

        // ⚠️ Sesuaikan kalau relasi kamu beda nama
        if (method_exists($u, 'adminScopes')) {
            $ids = $u->adminScopes()
                ->whereNotNull('block_id')
                ->pluck('block_id')
                ->unique()
                ->values()
                ->all();

            return count($ids) ? $ids : [];
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

                    // ✅ UBAH: residentProfile.full_name -> profile.full_name
                    Forms\Components\TextInput::make('profile.full_name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    // ✅ UBAH: residentProfile.resident_category_id -> profile.resident_category_id
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

                    // ✅ UBAH: residentProfile.is_international -> profile.is_international
                    Forms\Components\Toggle::make('profile.is_international')
                        ->label('Penghuni Luar Negeri?')
                        ->default(false),

                    // (sisanya profile.* kamu sudah benar)
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
                        ->disabled(fn (Forms\Get $get) =>
                            blank($get('block_id')) ||
                            blank($get('profile.gender')) ||
                            blank($get('profile.resident_category_id')) // ✅ FIX
                        )
                        ->helperText(function (Forms\Get $get) {
                            if (blank($get('profile.gender'))) return 'Pilih jenis kelamin dulu.';
                            if (blank($get('profile.resident_category_id'))) return 'Pilih kategori penghuni dulu.';
                            return 'Kamar hanya boleh untuk 1 gender dan 1 kategori penghuni.';
                        })
                        ->options(function (Forms\Get $get) {
                            $blockId    = $get('block_id');
                            $gender     = $get('profile.gender'); // M/F
                            $categoryId = $get('profile.resident_category_id'); // ✅ FIX

                            if (blank($blockId) || blank($gender) || blank($categoryId)) return [];

                            $rooms = Room::query()
                                ->where('block_id', $blockId)   // ✅ tidak akan bocor ke cabang lain
                                ->where('is_active', true)
                                ->with('residentCategory')      // ✅ agar bisa tampil nama kategori
                                ->orderBy('code')
                                ->get();

                            $options = [];

                            foreach ($rooms as $room) {
                                // RULE kategori kamar
                                if (! is_null($room->resident_category_id) && (int) $room->resident_category_id !== (int) $categoryId) {
                                    continue;
                                }

                                // RULE gender kamar
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

                                // ✅ tampilkan kategori kamar (nama)
                                $categoryLabel = $room->residentCategory?->name ?? 'Belum ditentukan';

                                $label = ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                                $label .= " — {$genderLabel} — Kategori: {$categoryLabel} — Kap: {$room->capacity}";

                                $options[$room->id] = $label;
                            }

                            return $options;
                        }),
                    // sisanya (tanggal masuk, pic) biarkan seperti kamu
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

                        if (! $active?->room) return '-';
                        $room = $active->room;
                        return ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn (Builder $p) => $p->where('gender', $value));
                        });
                    }),

                SelectFilter::make('dorm_id')
                    ->label('Cabang (Dorm)')
                    ->options(fn () => Dorm::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room.block', fn (Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    }),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'branch_admin'])),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => auth()->user()?->hasRole('super_admin') && $record?->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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

                            if (! $active?->room) return '-';

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
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListResidents::route('/'),
            'create' => Pages\CreateResident::route('/create'),
            'edit'   => Pages\EditResident::route('/{record}/edit'),
        ];
    }
}
