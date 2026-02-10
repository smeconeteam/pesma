<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionResource\Pages;
use App\Models\Institution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $slug = 'lembaga';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $modelLabel = 'Lembaga';

    protected static ?string $pluralModelLabel = 'Lembaga';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lembaga')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('legal_number')
                            ->label('Nomor Legalitas')
                            ->maxLength(255)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('institution_name')
                            ->label('Nama Lembaga')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('dormitory_name')
                            ->label('Nama Asrama')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1) ,

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Kontak')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->prefix('https://')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tentang Kami')
                    ->icon('heroicon-o-information-circle')
                    ->description('Konten ini akan ditampilkan di halaman "Tentang Kami" pada website publik.')
                    ->schema([
                        Forms\Components\RichEditor::make('about_content')
                            ->label('Konten Tentang Asrama')
                            ->helperText('Gunakan editor di bawah untuk menulis informasi lengkap tentang asrama. Gunakan toolbar untuk formatting.')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'heading',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->disableAllToolbarButtons(false)
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('institutions/about')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull()
                            ->nullable()
                            ->placeholder('Klik di sini untuk mulai menulis tentang asrama...'),
                    ]),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('institutions/logos')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->columnSpanFull()
                            ->storeFileNamesIn('logo_original_name'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewInstitution::route('/'),
            'edit' => Pages\EditInstitution::route('/edit'),
        ];
    }

    // Akses & Permission
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'main_admin']);
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
