<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationResource\Pages;
use App\Models\Country;
use App\Models\Dorm;
use App\Models\Registration;
use App\Models\ResidentCategory;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Pendaftaran';
    protected static ?string $pluralLabel = 'Pendaftaran';
    protected static ?string $modelLabel = 'Pendaftaran';
    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        // Semua admin bisa lihat menu
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canAccess(): bool
    {
        $u = auth()->user();
        // Semua admin bisa akses resource ini
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        // Hanya super_admin dan main_admin yang bisa lihat list/tabel
        return $u?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        // Semua admin bisa create (termasuk branch_admin dan block_admin)
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $u = auth()->user();
        // Hanya super_admin dan main_admin bisa edit, dan hanya jika status masih pending
        return $record->status === 'pending'
            && ($u?->hasAnyRole(['super_admin', 'main_admin']) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        $u = auth()->user();
        // Hanya super_admin dan main_admin bisa hapus
        // Hanya bisa hapus jika status pending atau rejected
        return in_array($record->status, ['pending', 'rejected'])
            && ($u?->hasAnyRole(['super_admin', 'main_admin']) ?? false);
    }

    public static function canView(Model $record): bool
    {
        $u = auth()->user();
        // Hanya super_admin dan main_admin bisa view detail
        return $u?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Akun')
                    ->description('Data akun untuk login resident')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama (Username)')
                            ->helperText('Boleh sama dengan nama lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->helperText('Kosongkan untuk generate otomatis (123456789)')
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : bcrypt('123456789'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Profil Calon Penghuni')
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
                            ->required(),

                        Forms\Components\TextInput::make('national_id')
                            ->label('NIK')
                            ->rule('regex:/^\d+$/')
                            ->helperText('Hanya angka')
                            ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                            ->required(),

                        Forms\Components\TextInput::make('student_id')
                            ->label('NIM/NIS')
                            ->required(),

                        Forms\Components\TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->required(),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(now()->subYears(6))
                            ->maxDate(now()->subYears(6))
                            ->helperText('Minimal usia 6 tahun')
                            ->required(),

                        Forms\Components\TextInput::make('university_school')
                            ->label('Universitas/Sekolah')
                            ->required(),

                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Foto')
                            ->directory('registrations')
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
                            ->helperText('Hanya angka (tanpa + / spasi)')
                            ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                            ->required(),

                        Forms\Components\TextInput::make('guardian_name')
                            ->label('Nama Wali')
                            ->nullable(),

                        Forms\Components\TextInput::make('guardian_phone_number')
                            ->label('No. HP Wali')
                            ->rule('regex:/^\d+$/')
                            ->helperText('Hanya angka (tanpa + / spasi)')
                            ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*'])
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Preferensi Kamar')
                    ->description('Rencana kamar yang diinginkan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('preferred_dorm_id')
                            ->label('Cabang Yang Diinginkan')
                            ->options(fn() => Dorm::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Forms\Components\Select::make('preferred_room_type_id')
                            ->label('Tipe Kamar Yang Diinginkan')
                            ->options(fn() => RoomType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Forms\Components\DatePicker::make('planned_check_in_date')
                            ->label('Rencana Tanggal Masuk')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->minDate(now())
                            ->default(now()->addDays(7))
                            ->helperText('Minimal hari ini')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $canApproveReject = $user?->hasAnyRole(['super_admin', 'main_admin']) ?? false;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => '-',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentCategory.name')
                    ->label('Kategori')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('preferredDorm.name')
                    ->label('Cabang Pilihan')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('planned_check_in_date')
                    ->label('Rencana Masuk')
                    ->date('d M Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->date('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->native(false),

                SelectFilter::make('preferred_dorm_id')
                    ->label('Cabang Pilihan')
                    ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(
                        fn(Registration $record) =>
                        $record->status === 'pending' &&
                            $canApproveReject
                    ),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn(Registration $record) =>
                        $record->status === 'pending' &&
                            $canApproveReject
                    )
                    ->url(fn(Registration $record) => static::getUrl('approve', ['record' => $record])),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn(Registration $record) =>
                        $record->status === 'pending' &&
                            $canApproveReject
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->helperText('Opsional, tapi disarankan untuk diisi')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->action(function (Registration $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Pendaftaran ditolak')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(
                        fn(Registration $record) =>
                        in_array($record->status, ['pending', 'rejected']) &&
                            $canApproveReject
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrations::route('/'),
            'create' => Pages\CreateRegistration::route('/create'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
            'view' => Pages\ViewRegistration::route('/{record}'),
            'approve' => Pages\ApproveRegistration::route('/{record}/approve'),
        ];
    }
}
