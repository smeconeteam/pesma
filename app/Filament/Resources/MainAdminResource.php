<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MainAdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; // TAMBAHKAN INI

class MainAdminResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'admin-utama';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Admin Utama';
    protected static ?string $pluralLabel = 'Admin Utama';
    protected static ?string $modelLabel = 'Admin Utama';
    protected static ?int $navigationSort = 49;
    protected static ?string $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    // PERBAIKI METHOD INI - TAMBAHKAN withoutGlobalScopes
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class, // TAMBAHKAN INI untuk include soft deleted records
            ])
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'main_admin'))
            ->with(['adminProfile' => function ($query) {
                $query->withTrashed(); // TAMBAHKAN INI untuk include soft deleted admin profiles
            }]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Akun')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->maxLength(255)
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated(fn (string $operation) => $operation === 'create'),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(255)
                        ->helperText(fn (string $operation) => $operation === 'edit' 
                            ? 'Kosongkan jika tidak ingin mengubah password.' 
                            : 'Minimal 8 karakter.'),
                ]),

            Forms\Components\Section::make('Data Pribadi')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('adminProfile.national_id')
                        ->label('NIK')
                        ->required()
                        ->numeric()
                        ->minLength(16)
                        ->maxLength(16)
                        ->helperText('Nomor Induk Kependudukan (16 digit)'),

                    Forms\Components\TextInput::make('adminProfile.full_name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('adminProfile.gender')
                        ->label('Jenis Kelamin')
                        ->required()
                        ->native(false)
                        ->options([
                            'M' => 'Laki-laki',
                            'F' => 'Perempuan',
                        ]),

                    Forms\Components\TextInput::make('adminProfile.phone_number')
                        ->label('Nomor WhatsApp')
                        ->required()
                        ->tel()
                        ->prefix('+62')
                        ->maxLength(20)
                        ->helperText('Contoh: 812345678'),

                    Forms\Components\Toggle::make('adminProfile.show_phone_on_landing')
                        ->label('Tampilkan di Halaman Kontak')
                        ->helperText('Jika diaktifkan, nomor WhatsApp akan ditampilkan di halaman kontak landing page.')
                        ->default(false)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('adminProfile.photo_path')
                        ->label('Foto Profil')
                        ->image()
                        ->directory('admin-photos')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->maxSize(2048)
                        ->columnSpanFull()
                        ->helperText('Format: JPG, PNG. Maksimal 2MB.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('adminProfile.photo_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->adminProfile?->full_name ?? $record->name)),

                Tables\Columns\TextColumn::make('adminProfile.full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('adminProfile.national_id')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('adminProfile.phone_number')
                    ->label('No. WhatsApp')
                    ->formatStateUsing(fn($state) => $state ? '+62' . $state : '-')
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('adminProfile.gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn($state) => $state === 'M' ? 'Laki-laki' : 'Perempuan')
                    ->colors([
                        'primary' => 'M',
                        'success' => 'F',
                    ]),

                Tables\Columns\IconColumn::make('adminProfile.show_phone_on_landing')
                    ->label('Tampil di Kontak')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'M' => 'Laki-laki',
                        'F' => 'Perempuan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('adminProfile', function (Builder $q) use ($data) {
                                $q->withTrashed()->where('gender', $data['value']); // TAMBAHKAN withTrashed()
                            });
                        }
                    })
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->native(false),
                
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->deleted_at === null),
                
                Tables\Actions\RestoreAction::make()
                    ->successNotificationTitle('Admin utama berhasil dipulihkan')
                    ->using(function (User $record) {
                        app(\App\Services\AdminPrivilegeService::class)->restoreMainAdmin($record);
                        return $record;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Admin Utama')
                    ->modalDescription('Apakah Anda yakin ingin menghapus admin utama ini? Data akan di-soft delete.')
                    ->successNotificationTitle('Admin utama berhasil dihapus')
                    ->visible(fn ($record) => $record->deleted_at === null)
                    ->using(function (User $record) {
                        app(\App\Services\AdminPrivilegeService::class)->deleteMainAdmin($record);
                        return $record;
                    }),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->modalHeading('Hapus Permanen Admin Utama')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen admin utama ini? Data tidak dapat dipulihkan!')
                    ->successNotificationTitle('Admin utama berhasil dihapus permanen')
                    ->using(function (User $record) {
                        app(\App\Services\AdminPrivilegeService::class)->forceDeleteMainAdmin($record);
                        return true;
                    }),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()); // TAMBAHKAN INI sebagai fallback
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMainAdmins::route('/'),
            'create' => Pages\CreateMainAdmin::route('/buat'),
            'edit' => Pages\EditMainAdmin::route('/{record}/edit'),
        ];
    }
}