<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyResource\Pages;
use App\Models\Policy;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Kebijakan & Ketentuan';
    protected static ?string $pluralLabel = 'Kebijakan & Ketentuan';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'admin_utama']) ?? false;
        // atau:
        // return $user?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('active');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Judul')
                ->required()
                ->maxLength(255),

            // Tanggal Bulan Tahun (tersimpan sesuai tanggal yang dipilih)
            DatePicker::make('published_at')
                ->label('Berlaku')
                ->native(false)
                ->displayFormat('d F Y')
                ->closeOnDateSelection()
                ->dehydrateStateUsing(function ($state) {
                    if (blank($state)) {
                        return null;
                    }

                    // Simpan tanggal yang dipilih (set awal hari biar konsisten)
                    return Carbon::parse($state)->startOfDay();
                }),

            RichEditor::make('content')
                ->label('Isi Kebijakan & Ketentuan')
                ->required()
                ->columnSpanFull()
                 ->toolbarButtons([
                    'bold',
                    'bulletList',
                    'h1',
                    'h2',
                    'h3',
                    'italic',
                    'link',
                    'orderedList',
                    'redo',
                    'underline',
                    'undo',
                 ]),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPolicies::route('/'),
            'active' => Pages\ViewActivePolicy::route('/active'),
            'edit'   => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
