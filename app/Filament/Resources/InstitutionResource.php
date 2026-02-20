<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Institution;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use App\Filament\Resources\InstitutionResource\Pages;

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
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Informasi Lembaga')
                            ->schema([
                                Forms\Components\Section::make('Informasi Dasar')
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
                                            ->columnSpan(1),

                                        Forms\Components\Textarea::make('address')
                                            ->label('Alamat')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
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
                            ]),

                        Tab::make('Kontak')
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

                        Tab::make('Tentang Kami')
                            ->schema([
                                Forms\Components\RichEditor::make('about_content')
                                    ->label('Konten Tentang Asrama')
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
                                        'attachFiles',
                                    ])
                                    ->disableAllToolbarButtons(false)
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('institutions/about')
                                    ->fileAttachmentsVisibility('public')
                                    ->columnSpanFull()
                                    ->mutateDehydratedStateUsing(function ($state) {

                                        if (! $state) return $state;

                                        libxml_use_internal_errors(true);

                                        $dom = new \DOMDocument('1.0', 'UTF-8');
                                        $dom->loadHTML('<div>' . $state . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                                        // 1. HAPUS figcaption
                                        while (($captions = $dom->getElementsByTagName('figcaption'))->length > 0) {
                                            $captions->item(0)->remove();
                                        }

                                        // 2. UNWRAP <a> TANPA MEMINDAHKAN <img>
                                        $links = [];
                                        foreach ($dom->getElementsByTagName('a') as $a) {
                                            $links[] = $a;
                                        }

                                        foreach ($links as $a) {
                                            $parent = $a->parentNode;

                                            while ($a->firstChild) {
                                                $parent->insertBefore($a->firstChild, $a);
                                            }

                                            $parent->removeChild($a);
                                        }

                                        return $dom->saveHTML();
                                    })
                                    ->nullable()
                                    ->placeholder('Klik di sini untuk mulai menulis tentang asrama...'),
                            ]),

                        Tab::make('Landing Page')
                            ->schema([
                                Forms\Components\Section::make('Headline & Deskripsi')
                                    ->description('Kustomisasi teks utama yang ditampilkan di halaman depan.')
                                    ->schema([
                                        Forms\Components\TextInput::make('landing_headline')
                                            ->label('Headline')
                                            ->placeholder('Contoh: Kuliah Sambil Mondok, atau Kos Aja, Bisa di PPM elFIRA')
                                            ->helperText('Teks judul besar di bagian atas landing page.')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('landing_description')
                                            ->label('Deskripsi')
                                            ->placeholder('Contoh: Menyediakan berbagai pilihan kamar dengan fasilitas lengkap dan harga terjangkau')
                                            ->helperText('Teks deskripsi di bawah headline.')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(2)
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
