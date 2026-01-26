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
                            ->default(fn(string $operation) => $operation === 'create' ? '123456789' : null)
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
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                // Reset preferensi saat kategori berubah
                                $set('preferred_dorm_id', null);
                                $set('preferred_room_type_id', null);
                            })
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

                        Forms\Components\TextInput::make('student_id')
                            ->label('NIM/NIS')
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
                    ->description('Tanggal pendaftaran dan pilihan kamar')
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
                            ->options(function (Forms\Get $get) {
                                $categoryId = $get('resident_category_id');
                                
                                if (blank($categoryId)) {
                                    return Dorm::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }

                                // Ambil dorm yang memiliki kamar dengan kategori yang sesuai ATAU kamar tanpa kategori
                                $dormIds = \Illuminate\Support\Facades\DB::table('rooms')
                                    ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                                    ->join('dorms', 'blocks.dorm_id', '=', 'dorms.id')
                                    ->where('rooms.is_active', true)
                                    ->where('blocks.is_active', true)
                                    ->where('dorms.is_active', true)
                                    ->where(function ($q) use ($categoryId) {
                                        // Kamar dengan kategori yang sama
                                        $q->where('rooms.resident_category_id', $categoryId)
                                        // ATAU kamar tanpa kategori (NULL)
                                        ->orWhereNull('rooms.resident_category_id');
                                    })
                                    ->distinct()
                                    ->pluck('dorms.id');

                                return Dorm::query()
                                    ->whereIn('id', $dormIds)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->native(false)
                            ->live() // ✅ Reactive saat kategori berubah
                            ->afterStateUpdated(function (Forms\Set $set) {
                                // Reset tipe kamar saat cabang berubah
                                $set('preferred_room_type_id', null);
                            })
                            ->disabled(fn (Forms\Get $get) => blank($get('resident_category_id')))
                            ->helperText(fn (Forms\Get $get) => 
                                blank($get('resident_category_id'))
                                    ? 'Pilih kategori penghuni terlebih dahulu'
                                    : 'Hanya cabang dengan kamar sesuai kategori yang ditampilkan'
                            )
                            ->nullable(),

                        Forms\Components\Select::make('preferred_room_type_id')
                            ->label('Tipe Kamar Yang Diinginkan')
                            ->options(function (Forms\Get $get) {
                                $categoryId = $get('resident_category_id');
                                $dormId = $get('preferred_dorm_id');
                                
                                if (blank($categoryId) || blank($dormId)) {
                                    return [];
                                }

                                // Ambil tipe kamar yang tersedia di cabang ini dengan kategori yang sesuai
                                $roomTypeIds = \Illuminate\Support\Facades\DB::table('rooms')
                                    ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                                    ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
                                    ->where('blocks.dorm_id', $dormId)
                                    ->where('rooms.is_active', true)
                                    ->where('room_types.is_active', true)
                                    ->where(function ($q) use ($categoryId) {
                                        $q->where('rooms.resident_category_id', $categoryId)
                                        ->orWhereNull('rooms.resident_category_id');
                                    })
                                    ->distinct()
                                    ->pluck('room_types.id');

                                return RoomType::query()
                                    ->whereIn('id', $roomTypeIds)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->native(false)
                            ->live() // ✅ Reactive saat cabang/kategori berubah
                            ->disabled(function (Forms\Get $get) {
                                return blank($get('resident_category_id')) || blank($get('preferred_dorm_id'));
                            })
                            ->helperText(function (Forms\Get $get) {
                                if (blank($get('resident_category_id'))) {
                                    return 'Pilih kategori penghuni terlebih dahulu';
                                }
                                if (blank($get('preferred_dorm_id'))) {
                                    return 'Pilih cabang terlebih dahulu';
                                }
                                return 'Hanya tipe kamar yang tersedia di cabang terpilih';
                            })
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
                Tables\Actions\ActionGroup::make([

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
                        ->visible(fn(Registration $record) => !$record->hasRegistrationBill() && $canApproveReject && $record->status === 'approved')
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
