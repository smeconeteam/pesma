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
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

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
        // Hanya bisa edit jika status masih pending
        return $record->status === 'pending'
            && ($u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        $u = auth()->user();
        // Hanya bisa hapus jika status pending atau rejected
        return in_array($record->status, ['pending', 'rejected'])
            && ($u?->hasAnyRole(['super_admin', 'branch_admin']) ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'residentCategory',
                'country',
                'preferredDorm',
                'preferredRoomType',
                'approvedBy',
                'user',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                        ->nullable(),

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
                ->description('Rencana kamar yang diinginkan (tidak mengikat)')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('preferred_dorm_id')
                        ->label('Cabang Yang Diinginkan')
                        ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Forms\Components\Select::make('preferred_room_type_id')
                        ->label('Tipe Kamar Yang Diinginkan')
                        ->options(fn() => RoomType::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Forms\Components\DatePicker::make('planned_check_in_date')
                        ->label('Rencana Tanggal Masuk')
                        ->native(false)
                        ->default(now()->addDays(7))
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
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
                    ->visible(fn(Registration $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Registration $record) => $record->status === 'pending')
                    ->url(fn(Registration $record) => static::getUrl('approve', ['record' => $record])),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Registration $record) => $record->status === 'pending')
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
                        in_array($record->status, ['pending', 'rejected'])
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
