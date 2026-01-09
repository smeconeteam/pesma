<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Tipe Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Tipe Kamar';
    protected static ?string $modelLabel = 'Tipe Kamar';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Tipe Kamar')
                ->columns(2)
                ->schema([
                    // 1) Nama Tipe Kamar - FULL
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Tipe Kamar (mis. VVIP 1)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $set('code', static::buildCode($state));
                        }),

                    // 2) Kode Tipe Kamar (Otomatis) - FULL
                    Forms\Components\TextInput::make('code')
                        ->label('Kode Tipe Kamar (Otomatis)')
                        ->disabled()
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Kode otomatis dibuat dari nama (huruf kecil, spasi jadi "-")')
                        ->afterStateHydrated(function (Set $set, Get $get, $state) {
                            $computed = static::buildCode($get('name'));
                            $set('code', $state ?: $computed);
                        })
                        ->dehydrated(true)
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            return static::buildCode($get('name'));
                        }),

                    // 3) Kapasitas Default - KIRI
                    Forms\Components\TextInput::make('default_capacity')
                        ->label('Kapasitas Default')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->helperText('Jumlah penghuni default dalam satu kamar.')
                        ->columnSpan(1),

                    // 4) Tarif Bulanan Default - KANAN
                    Forms\Components\TextInput::make('default_monthly_rate')
                        ->label('Tarif Bulanan Default')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('Rp')
                        ->columnSpan(1),

                    // 5) Deskripsi - FULL
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),

                    // 6) Aktif
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tipe')
                    ->sortable()
                    ->searchable()
                    // ✅ fallback kalau data lama masih kosong
                    ->formatStateUsing(fn ($state, RoomType $record) => $state ?: static::buildAutoName($record->base_name, $record->default_capacity)),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('default_capacity')
                    ->label('Kapasitas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Nonaktif',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(function (RoomType $record) {
                        $user = auth()->user();

                        if (!$user?->hasRole(['super_admin', 'main_admin'])) {
                            return false;
                        }

                        if (method_exists($record, 'trashed') && $record->trashed()) {
                            return false;
                        }

                        return true;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (RoomType $record): bool =>
                        auth()->user()?->hasRole(['super_admin', 'main_admin'])
                        && ! $record->trashed()
                        && ! $record->rooms()->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn (RoomType $record): bool =>
                        auth()->user()?->hasRole(['super_admin'])
                        && $record->trashed()
                    ),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn(RoomType $record): bool =>
                        auth()->user()?->hasRole('super_admin')
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(
                        fn($livewire = null): bool =>
                        auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && (($livewire?->activeTab ?? 'aktif') === 'aktif')
                    )
                    ->action(function (Collection $records) {
                        $allowed = $records->filter(fn(RoomType $r) => ! $r->rooms()->exists());
                        $blocked = $records->diff($allowed);

                        if ($allowed->isEmpty()) {
                            Notification::make()
                                ->title('Aksi Dibatalkan')
                                ->body('Tidak ada tipe kamar yang bisa dihapus. Tipe kamar yang sudah dipakai oleh kamar tidak dapat dihapus.')
                                ->warning()
                                ->send();
                            return;
                        }

                        foreach ($allowed as $r) {
                            $r->delete();
                        }

                        $deleted = $allowed->count();

                        if ($blocked->isNotEmpty()) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil menghapus {$deleted} tipe kamar. Tipe kamar yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil menghapus {$deleted} tipe kamar.")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\RestoreBulkAction::make()
                    ->visible(
                        fn($livewire = null): bool =>
                        auth()->user()?->hasRole('super_admin')
                            && (($livewire?->activeTab ?? 'aktif') === 'terhapus')
                    ),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Detail Tipe Kamar')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama Tipe Kamar')
                        ->placeholder('-'),

                    TextEntry::make('code')
                        ->label('Kode Tipe Kamar')
                        ->badge()
                        ->color('info')
                        ->placeholder('-'),

                    TextEntry::make('default_capacity')
                        ->label('Kapasitas Default')
                        ->suffix(' orang')
                        ->placeholder('-'),

                    TextEntry::make('default_monthly_rate')
                        ->label('Tarif Bulanan Default')
                        ->money('IDR', true),

                    IconEntry::make('is_active')
                        ->label('Aktif')
                        ->boolean(),

                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit'   => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }

    public static function buildCode(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '';
        }

        return Str::slug($name, '-');
    }
}
