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

    // ✅ Hanya super_admin & main_admin yang melihat menu
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    // ✅ Hanya super_admin & main_admin yang bisa akses
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
                    // 1) Nama (VIP) - FULL
                    Forms\Components\TextInput::make('base_name')
                        ->label('Nama (mis. VIP)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            $set('name', static::buildAutoName($state));
                        }),

                    // 2) Kapasitas Default - KIRI
                    Forms\Components\TextInput::make('default_capacity')
                        ->label('Kapasitas Default')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->helperText('Jumlah penghuni default dalam satu kamar.')
                        ->columnSpan(1),

                    // 3) Tarif Bulanan Default - KANAN
                    Forms\Components\TextInput::make('default_monthly_rate')
                        ->label('Tarif Bulanan Default')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('Rp')
                        ->columnSpan(1),

                    // 4) Nama Tipe (Otomatis) - FULL
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Tipe (Otomatis)')
                        ->disabled()
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        // ✅ Saat buka edit, pastikan terisi (kalau DB kosong)
                        ->afterStateHydrated(function (Set $set, Get $get, $state) {
                            $computed = static::buildAutoName($get('base_name'));
                            $set('name', $state ?: $computed);
                        })
                        // ✅ PENTING: Saat submit, hitung ulang walaupun user belum blur
                        ->dehydrated(true)
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            return static::buildAutoName($get('base_name'));
                        }),

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
                    ->formatStateUsing(fn ($state, RoomType $record) => $state ?: static::buildAutoName($record->base_name)),

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

                // ✅ Force Delete hanya tampil untuk data terhapus
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(fn (RoomType $record): bool =>
                        auth()->user()?->hasRole('super_admin')
                        && $record->trashed()
                    ),
            ])
            ->bulkActions([
                    // ✅ Bulk Delete hanya di tab data aktif
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($livewire = null): bool =>
                            auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && (($livewire?->activeTab ?? 'aktif') === 'aktif')
                        )
                        ->action(function (Collection $records) {
                            $allowed = $records->filter(fn (RoomType $r) => ! $r->rooms()->exists());
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

                    // ✅ Bulk Restore hanya di tab data terhapus
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn ($livewire = null): bool =>
                            auth()->user()?->hasRole('super_admin')
                            && (($livewire?->activeTab ?? 'aktif') === 'terhapus')
                        ),
            ]);
    }

    /**
     * ✅ Supaya saat ViewAction "Lihat", field tampil jelas
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Detail Tipe Kamar')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama Tipe')
                        ->state(fn (RoomType $record) => $record->name ?: static::buildAutoName($record->base_name))
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

    // ✅ Nama otomatis: lowercase + "-" untuk spasi (slug)
    public static function buildAutoName(?string $baseName): string
    {
        $baseName = trim((string) $baseName);

        if ($baseName === '') {
            return '';
        }

        // contoh: "VIP Room" => "vip-room"
        return Str::slug($baseName, '-');
    }
}
