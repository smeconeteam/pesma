<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Models\Country;
use App\Models\ResidentCategory;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Data Penghuni';
    protected static ?string $pluralLabel = 'Data Penghuni';
    protected static ?string $modelLabel = 'Data Penghuni';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
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
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Akun')
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Profil Penghuni')
                ->relationship('residentProfile')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('photo_path')
                        ->label('Foto')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->directory('resident-photos')
                        ->visibility('private')
                        ->maxSize(2048)
                        ->helperText('Maksimal 2MB, format: JPG, PNG')
                        ->columnSpanFull()
                        ->nullable(),

                    Forms\Components\Select::make('resident_category_id')
                        ->label('Kategori Penghuni')
                        ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('full_name')
                        ->label('Nama Lengkap')
                        ->required(),

                    Forms\Components\Select::make('gender')
                        ->label('Gender')
                        ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('student_id')
                        ->label('NIM/NIS')
                        ->nullable(),

                    Forms\Components\TextInput::make('national_id')
                        ->label('NIK')
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka.')
                        ->nullable(),

                    Forms\Components\TextInput::make('birth_place')
                        ->label('Tempat Lahir')
                        ->nullable(),

                    Forms\Components\DatePicker::make('birth_date')
                        ->label('Tanggal Lahir')
                        ->nullable()
                        ->native(false),

                    Forms\Components\TextInput::make('university_school')
                        ->label('Universitas/Sekolah')
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Kewarganegaraan & Kontak')
                ->relationship('residentProfile')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('citizenship_status')
                        ->label('Status Kewarganegaraan')
                        ->options(['WNI' => 'WNI', 'WNA' => 'WNA'])
                        ->native(false)
                        ->live()
                        ->required()
                        ->default('WNI')
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === 'WNI') {
                                $indoId = Country::query()->where('iso2', 'ID')->value('id');
                                if ($indoId) {
                                    $set('country_id', $indoId);
                                }
                            }
                        }),

                    Forms\Components\Select::make('country_id')
                        ->label('Asal Negara')
                        ->options(fn() => Country::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->disabled(fn(Forms\Get $get) => $get('citizenship_status') === 'WNI')
                        ->required()
                        ->default(fn() => Country::query()->where('iso2', 'ID')->value('id')),

                    Forms\Components\TextInput::make('phone_number')
                        ->label('Nomor HP')
                        ->helperText('Contoh: 6281234567890')
                        ->nullable(),

                    Forms\Components\TextInput::make('guardian_name')
                        ->label('Nama Wali')
                        ->nullable(),

                    Forms\Components\TextInput::make('guardian_phone_number')
                        ->label('Nomor HP Wali')
                        ->helperText('Contoh: 6281234567890')
                        ->nullable(),

                    Forms\Components\DatePicker::make('check_in_date')
                        ->label('Tanggal Masuk')
                        ->nullable()
                        ->native(false),

                    Forms\Components\DatePicker::make('check_out_date')
                        ->label('Tanggal Keluar')
                        ->nullable()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('residentProfile.photo_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('residentProfile.full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('residentProfile.residentCategory.name')
                    ->label('Kategori')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('residentProfile.citizenship_status')
                    ->label('WN')
                    ->colors(['success' => 'WNI', 'warning' => 'WNA'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('residentProfile.country.name')
                    ->label('Negara')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('room_active')
                    ->label('Kamar Aktif')
                    ->state(function (User $record) {
                        $active = $record->roomResidents()
                            ->whereNull('room_residents.check_out_date')
                            ->latest('check_in_date')
                            ->first();

                        return $active?->room?->code ?? '-';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('citizenship_status')
                    ->label('Kewarganegaraan')
                    ->options(['WNI' => 'WNI', 'WNA' => 'WNA'])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas('residentProfile', fn(Builder $q) => $q->where('citizenship_status', $value));
                    })
                    ->native(false),

                SelectFilter::make('resident_category_id')
                    ->label('Kategori')
                    ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas('residentProfile', fn(Builder $q) => $q->where('resident_category_id', $value));
                    })
                    ->native(false),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas('residentProfile', fn(Builder $q) => $q->where('gender', $value));
                    })
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'branch_admin']) ?? false),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(
                        fn(User $record): bool =>
                        ! $record->trashed()
                            && ! RoomResident::query()->where('user_id', $record->id)->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(
                        fn(User $record): bool =>
                        auth()->user()?->hasRole('super_admin')
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(fn(): bool => request()->query('activeTab', 'aktif') !== 'terhapus')
                        ->action(function (Collection $records) {

                            $allowed = $records->filter(
                                fn(User $u) =>
                                ! RoomResident::query()->where('user_id', $u->id)->exists()
                            );

                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada penghuni yang bisa dihapus. Penghuni yang sudah pernah ditempatkan di kamar tidak dapat dihapus.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $allowed->each->delete();
                            $deleted = $allowed->count();

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Berhasil Sebagian')
                                    ->body("Berhasil menghapus {$deleted} penghuni. Yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} penghuni.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih')
                        ->visible(
                            fn(): bool =>
                            auth()->user()?->hasRole('super_admin')
                                && request()->query('activeTab') === 'terhapus'
                        ),
                ]),
            ]);
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
