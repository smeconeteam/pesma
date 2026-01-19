<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Resources\MyProfileResource\Pages;

class MyProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'profile-saya';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profile Saya';
    
    protected static ?string $modelLabel = 'Profile';
    
    protected static ?string $pluralModelLabel = 'Profile';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->description('Data login dan informasi akun')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Panggilan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make('Keamanan')
                    ->description('Update password Anda (opsional, kosongkan jika tidak ingin mengubah)')
                    ->schema([
                        Forms\Components\TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('new_password_confirmation')
                            ->dehydrated(false)
                            ->helperText('Minimal 8 karakter'),
                        
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditMyProfile),
                
                Forms\Components\Section::make('Informasi Profile Admin')
                    ->description('Data lengkap profile admin')
                    ->relationship('adminProfile')
                    ->schema([
                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Foto Profile')
                            ->disk('public')
                            ->directory('admin-profiles')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone_number')
                            ->label('No. Handphone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('08123456789'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewMyProfile::route('/'),
            'edit' => Pages\EditMyProfile::route('/edit'),
        ];
    }

    // Akses & Permission
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->roles()->where('name', 'main_admin')->exists();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}