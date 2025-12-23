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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query
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
                                ->disabled(),

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

            Forms\Components\Section::make('Informasi Kamar')
                ->description('Data penempatan kamar hanya bisa dikelola melalui menu Penempatan Kamar.')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('current_room_info')
                        ->label('Kamar Saat Ini')
                        ->content(function ($record) {
                            if (!$record) return '-';

                            $active = $record->roomResidents()
                                ->whereNull('room_residents.check_out_date')
                                ->latest('check_in_date')
                                ->first();

                            if (!$active || !$active->room) return '-';

                            $room = $active->room;
                            $block = $room->block;
                            $dorm = $block->dorm;

                            return "{$dorm->name} - {$block->name} - {$room->code}";
                        })
                        ->columnSpanFull(),
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
                    ->label('Kewarganegaraan')
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

                SelectFilter::make('status')
                    ->label('Status Penghuni')
                    ->options([
                        'registered' => 'Terdaftar',
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('status', $value));
                        });
                    })
                    ->native(false),

                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('gender', $value));
                        });
                    })
                    ->native(false),

                SelectFilter::make('citizenship_status')
                    ->label('Kewarganegaraan')
                    ->options(['WNI' => 'WNI', 'WNA' => 'WNA'])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('citizenship_status', $value));
                        });
                    })
                    ->native(false),

                SelectFilter::make('resident_category_id')
                    ->label('Kategori Penghuni')
                    ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $categoryId) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('resident_category_id', $categoryId));
                        });
                    })
                    ->native(false),

                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('room_residents.check_out_date')
                                    ->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    })
                    ->native(false),

                SelectFilter::make('block_id')
                    ->label('Komplek')
                    ->options(fn() => Block::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $blockId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($blockId) {
                                $rr->whereNull('room_residents.check_out_date')
                                    ->whereHas('room', fn(Builder $r) => $r->where('block_id', $blockId));
                            });
                        });
                    })
                    ->native(false),

                TernaryFilter::make('has_room')
                    ->label('Memiliki Kamar')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Ada Kamar')
                    ->falseLabel('Belum Ada Kamar')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas(
                            'roomResidents',
                            fn(Builder $q) => $q->whereNull('check_out_date')
                        ),
                        false: fn(Builder $query) => $query->whereDoesntHave(
                            'roomResidents',
                            fn(Builder $q) => $q->whereNull('check_out_date')
                        ),
                    ),

                TrashedFilter::make()
                    ->label('Status Data')
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
            'edit'   => Pages\EditResident::route('/{record}/edit'),
            'view'   => Pages\ViewResident::route('/{record}'),
        ];
    }
}
