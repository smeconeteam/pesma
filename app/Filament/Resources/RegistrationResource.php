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

    protected static ?string $slug = 'pendaftaran';
    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Pendaftaran';
    protected static ?string $pluralLabel = 'Pendaftaran';
    protected static ?string $modelLabel = 'Pendaftaran';
    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $u = auth()->user();
        return $record->status === 'pending'
            && ($u?->hasAnyRole(['super_admin', 'main_admin']) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        $u = auth()->user();
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
                            ->label('Nama Panggilan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText(fn(string $operation) => $operation === 'edit'
                                ? 'Kosongkan jika tidak ingin mengubah password.'
                                : 'Password default: 123456789'),
                    ]),

                Forms\Components\Section::make('Profil Calon Penghuni')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('resident_category_id')
                            ->label('Kategori Penghuni')
                            ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(3)
                                    ->maxLength(500),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return ResidentCategory::create($data)->id;
                            }),

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
                            ->default(now()->subYears(6)->startOfDay())
                            ->maxDate(now()->subYears(6))
                            ->helperText('Minimal usia 6 tahun. Default: 6 tahun yang lalu')
                            ->extraAttributes([
                                'x-data' => '{ init() { this.$nextTick(() => { if (this.$el.querySelector("input[type=text]")) { this.$el.querySelector("input[type=text]").dispatchEvent(new Event("click")); } }) } }'
                            ])
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
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state === 'WNI' && blank($get('country_id'))) {
                                    $indoId = Country::query()->where('iso2', 'ID')->value('id');
                                    if ($indoId) {
                                        $set('country_id', $indoId);
                                    }
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make('country_id')
                            ->label('Asal Negara')
                            ->options(fn() => Country::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->default(fn() => Country::query()->where('iso2', 'ID')->value('id'))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Negara')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('calling_code')
                                    ->label('Kode Telepon')
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->createOptionUsing(fn(array $data) => Country::firstOrCreate(
                                ['name' => $data['name']],
                                ['calling_code' => $data['calling_code'] ?? null]
                            )->id)
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

                Forms\Components\Section::make('Informasi Pendaftaran')
                    ->description('Tanggal pendaftaran dan preferensi kamar')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Tanggal Pendaftaran')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(now())
                            ->helperText('Default: Hari ini. Bisa diubah untuk data historis')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('preferred_dorm_id')
                            ->label('Cabang Yang Diinginkan')
                            ->options(fn() => Dorm::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->nullable(),

                        Forms\Components\Select::make('preferred_room_type_id')
                            ->label('Tipe Kamar Yang Diinginkan')
                            ->options(fn() => RoomType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->nullable(),

                        Forms\Components\DatePicker::make('planned_check_in_date')
                            ->label('Rencana Tanggal Masuk')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(now()->addDays(7))
                            ->helperText('Default: 1 minggu dari hari ini. Bisa diubah sesuai kebutuhan')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Biaya Pendaftaran (Opsional)')
                    ->description('Buat tagihan biaya pendaftaran sekaligus saat mendaftarkan penghuni')
                    ->collapsed()
                    ->visible(fn(string $operation) => $operation === 'create')
                    ->schema([
                        Forms\Components\Toggle::make('generate_registration_bill')
                            ->label('Generate Tagihan Biaya Pendaftaran?')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->visible(fn(Forms\Get $get) => $get('generate_registration_bill'))
                            ->schema([
                                Forms\Components\TextInput::make('registration_fee_amount')
                                    ->label('Nominal Biaya')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(500000)
                                    ->required(fn(Forms\Get $get) => $get('generate_registration_bill'))
                                    ->minValue(0),

                                Forms\Components\TextInput::make('registration_fee_discount')
                                    ->label('Diskon (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100),

                                Forms\Components\DatePicker::make('registration_fee_due_date')
                                    ->label('Jatuh Tempo')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d')
                                    ->default(now()->addWeeks(2))
                                    ->minDate(now())
                                    ->helperText('Default: 2 minggu dari hari ini. Opsional, bisa dikosongkan')
                                    ->nullable(),
                            ]),

                        Forms\Components\Placeholder::make('registration_fee_info')
                            ->label('')
                            ->visible(fn(Forms\Get $get) => $get('generate_registration_bill'))
                            ->content(function (Forms\Get $get) {
                                $amount = $get('registration_fee_amount') ?? 0;
                                $discount = $get('registration_fee_discount') ?? 0;
                                $total = $amount - (($amount * $discount) / 100);

                                return new \Illuminate\Support\HtmlString("
                                    <div class='rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800'>
                                        <div class='flex items-start gap-3'>
                                            <svg class='w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                                            </svg>
                                            <div class='flex-1'>
                                                <div class='font-semibold text-blue-900 dark:text-blue-100 mb-1'>Total Tagihan</div>
                                                <div class='text-2xl font-bold text-blue-600 dark:text-blue-400'>
                                                    Rp " . number_format($total, 0, ',', '.') . "
                                                </div>
                                                <div class='text-sm text-blue-700 dark:text-blue-300 mt-2'>
                                                    Tagihan akan dibuat otomatis dengan status <strong>Tertagih</strong> setelah pendaftaran berhasil.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
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
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->date('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_registration_bill')
                    ->label('Tagihan')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->hasRegistrationBill())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn($record) => $record->hasRegistrationBill()
                        ? 'Sudah ada tagihan pendaftaran'
                        : 'Belum ada tagihan pendaftaran')
                    ->toggleable(),
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
                    ->visible(fn(Registration $record) => $record->status === 'pending' && $canApproveReject),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Registration $record) => $record->status === 'pending' && $canApproveReject)
                    ->url(fn(Registration $record) => static::getUrl('approve', ['record' => $record])),

                Tables\Actions\Action::make('generate_bill')
                    ->label('Buat Tagihan')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn(Registration $record) => !$record->hasRegistrationBill())
                    ->url(fn(Registration $record) => route('filament.admin.resources.tagihan.create', [
                        'registration_id' => $record->id,
                        'auto_fill' => true,
                    ])),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Registration $record) => $record->status === 'pending' && $canApproveReject)
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

                Tables\Actions\Action::make('force_delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(Registration $record) => static::canForceDelete($record))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Pendaftaran')
                    ->modalDescription(function (Registration $record): string {
                        $desc = 'Apakah Anda yakin ingin menghapus permanen data pendaftaran ini? Data yang terhapus permanen tidak dapat dipulihkan.';

                        if ($record->status === 'approved') {
                            $desc .= "\n\nCatatan: status pendaftaran ini sudah disetujui.";
                        }

                        if (!blank($record->user_id)) {
                            $desc .= "\n\nCatatan: pendaftaran ini terhubung ke akun user_id: {$record->user_id}. Aksi ini hanya menghapus data pendaftaran, bukan akun user.";
                        }

                        return $desc;
                    })
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->action(fn(Registration $record) => $record->forceDelete()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrations::route('/'),
            'create' => Pages\CreateRegistration::route('/buat'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
            'view' => Pages\ViewRegistration::route('/{record}'),
            'approve' => Pages\ApproveRegistration::route('/{record}/setujui'),
        ];
    }
}