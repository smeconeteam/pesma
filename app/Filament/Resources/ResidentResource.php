<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Models\Block;
use App\Models\Country;
use App\Models\Dorm;
use App\Models\ResidentCategory;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Group;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Data Penghuni';
    protected static ?string $pluralLabel = 'Data Penghuni';
    protected static ?string $modelLabel = 'Data Penghuni';
    protected static ?int $navigationSort = 30;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->with([
                'roles',
                'residentProfile.residentCategory',
                'residentProfile.country',
                'roomResidents.room.block.dorm',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Akun')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama (Akun)')
                        ->helperText('Boleh sama dengan nama lengkap.')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),

            Group::make()
                ->relationship('residentProfile')
                ->schema([
                    Forms\Components\Section::make('Profil Penghuni')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('resident_category_id')
                                ->label('Kategori Penghuni')
                                ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->required(),

                            Forms\Components\TextInput::make('full_name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('gender')
                                ->label('Jenis Kelamin')
                                ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                                ->native(false)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    // reset pilihan kamar jika gender berubah
                                    $set('../../room_assignment.room_id', null);
                                    $set('../../room_assignment.is_pic', false);
                                }),

                            Forms\Components\TextInput::make('national_id')
                                ->label('NIK')
                                ->rule('regex:/^\d+$/')
                                ->helperText('Hanya angka.')
                                ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                                ->nullable(),

                            Forms\Components\TextInput::make('student_id')
                                ->label('NIM')
                                ->nullable(),

                            Forms\Components\TextInput::make('birth_place')
                                ->label('Tempat Lahir')
                                ->nullable(),

                            Forms\Components\DatePicker::make('birth_date')
                                ->label('Tanggal Lahir')
                                ->native(false)
                                ->nullable(),

                            Forms\Components\TextInput::make('university_school')
                                ->label('Universitas/Sekolah')
                                ->nullable(),

                            Forms\Components\FileUpload::make('photo_path')
                                ->label('Foto')
                                ->directory('residents')
                                ->image()
                                ->imageEditor()
                                ->columnSpanFull()
                                ->nullable(),
                        ]),

                    Forms\Components\Section::make('Kewarganegaraan & Kontak')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('citizenship_status')
                                ->label('Kewarganegaraan')
                                ->options(['WNI' => 'WNI', 'WNA' => 'WNA'])
                                ->native(false)
                                ->default('WNI')
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state === 'WNI') {
                                        $indoId = Country::query()->where('iso2', 'ID')->value('id');
                                        if ($indoId) $set('country_id', $indoId);
                                    }
                                })
                                ->required(),

                            Forms\Components\Select::make('country_id')
                                ->label('Asal Negara')
                                ->options(fn() => Country::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->disabled(fn(Forms\Get $get) => $get('citizenship_status') === 'WNI')
                                ->default(fn() => Country::query()->where('iso2', 'ID')->value('id'))
                                ->required(),

                            Forms\Components\TextInput::make('phone_number')
                                ->label('No. HP')
                                ->rule('regex:/^\d+$/')
                                ->helperText('Hanya angka (tanpa + / spasi).')
                                ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                                ->nullable(),

                            Forms\Components\TextInput::make('guardian_name')
                                ->label('Nama Wali')
                                ->nullable(),

                            Forms\Components\TextInput::make('guardian_phone_number')
                                ->label('No. HP Wali')
                                ->rule('regex:/^\d+$/')
                                ->helperText('Hanya angka (tanpa + / spasi).')
                                ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                                ->nullable(),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\Section::make('Penempatan Kamar')
                ->description('Data ini akan membuat record di room_residents saat disimpan.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('room_assignment.dorm_id')
                        ->label('Cabang (Dorm)')
                        ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('room_assignment.block_id', null);
                            $set('room_assignment.room_id', null);
                        })
                        ->required(false),

                    Forms\Components\Select::make('room_assignment.block_id')
                        ->label('Blok')
                        ->options(fn(Forms\Get $get) => Block::query()
                            ->when($get('room_assignment.dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->required(fn(Forms\Get $get) => filled($get('room_assignment.room_id')))
                        ->disabled(fn(Forms\Get $get) => blank($get('room_assignment.dorm_id')))
                        ->afterStateUpdated(fn(Forms\Set $set) => $set('room_assignment.room_id', null)),

                    Forms\Components\Select::make('room_assignment.room_id')
                        ->label('Kamar')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->required(fn(Forms\Get $get) => filled($get('room_assignment.block_id')))

                        ->disabled(fn(Forms\Get $get) => blank($get('room_assignment.block_id')) || blank($get('residentProfile.gender')))
                        ->options(function (Forms\Get $get) {
                            $blockId = $get('room_assignment.block_id');
                            $gender  = $get('residentProfile.gender');
                            if (blank($blockId) || blank($gender)) return [];

                            $rooms = Room::query()
                                ->where('block_id', $blockId)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get();

                            $options = [];

                            foreach ($rooms as $room) {
                                $activeGender = RoomResident::query()
                                    ->where('room_residents.room_id', $room->id)
                                    ->whereNull('room_residents.check_out_date')
                                    ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                                    ->value('resident_profiles.gender');

                                // kamar kosong => ok, terisi => harus sama gender
                                if ($activeGender && $activeGender !== $gender) continue;

                                $labelGender = $activeGender
                                    ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                    : 'Kosong';

                                $options[$room->id] = "{$room->code} — {$labelGender}";
                            }

                            return $options;
                        })
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (blank($state)) return;

                            $hasPic = RoomResident::query()
                                ->where('room_id', $state)
                                ->whereNull('check_out_date')
                                ->where('is_pic', true)
                                ->exists();

                            if ($hasPic) $set('room_assignment.is_pic', false);
                        }),

                    Forms\Components\DatePicker::make('room_assignment.check_in_date')
                        ->label('Tanggal Masuk')
                        ->required()
                        ->default(now()->toDateString())
                        ->native(false),

                    Forms\Components\Toggle::make('room_assignment.is_pic')
                        ->label('Jadikan PIC?')
                        ->default(false)
                        ->required(fn(Forms\Get $get) => filled($get('room_assignment.room_id')))
                        ->disabled(fn(Forms\Get $get) => blank($get('room_assignment.dorm_id')))
                        ->disabled(function (Forms\Get $get) {
                            $roomId = $get('room_assignment.room_id');
                            if (blank($roomId)) return true;

                            return RoomResident::query()
                                ->where('room_id', $roomId)
                                ->whereNull('check_out_date')
                                ->where('is_pic', true)
                                ->exists();
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

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('residentProfile.citizenship_status')
                    ->label('WN')
                    ->colors(['success' => 'WNI', 'warning' => 'WNA'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.residentCategory.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentProfile.phone_number')
                    ->label('No. HP')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('current_room')
                    ->label('Kamar Aktif')
                    ->state(function (User $record) {
                        $active = $record->roomResidents()
                            ->whereNull('room_residents.check_out_date')
                            ->latest('check_in_date')
                            ->first();

                        return $active?->room?->code ?? '-';
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
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('gender', $value));
                        });
                    })
                    ->native(false),

                SelectFilter::make('dorm_id')
                    ->label('Cabang (Dorm)')
                    ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('room_residents.check_out_date')
                                    ->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    }),

                TrashedFilter::make()
                    ->visible(fn() => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->url(fn(User $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\EditAction::make()->label('Edit'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false),

                Tables\Actions\RestoreAction::make()
                    ->label('Restore')
                    ->visible(fn(User $record) => (auth()->user()?->hasRole('super_admin') ?? false) && method_exists($record, 'trashed') && $record->trashed()),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListResidents::route('/'),
            'create' => Pages\CreateResident::route('/create'),
            'edit'   => Pages\EditResident::route('/{record}/edit'),
            'view'   => Pages\ViewResident::route('/{record}'),
        ];
    }
}
