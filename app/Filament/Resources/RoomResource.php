<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Dorm;
use App\Models\Room;
use Filament\Tables;
use App\Models\Block;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\RoomType;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RoomResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RoomResource\RelationManagers;
use Filament\Forms\Components\Select;
use App\Models\ResidentCategory;


class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Kamar Asrama';
    protected static ?string $modelLabel = 'Kamar Asrama';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kamar')
                    ->schema([
                        Select::make('dorm_id')
                            ->label('Cabang')
                            ->dehydrated(false)
                            ->options(function () {
                                return Dorm::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                // saat edit: set dorm_id dari relasi block->dorm
                                if ($record?->block?->dorm_id) {
                                    $component->state($record->block->dorm_id);
                                }
                            })
                            ->afterStateUpdated(function (Set $set) {
                                $set('block_id', null);
                                $set('code', null);
                            }),

                        Select::make('block_id')
                            ->label('Komplek')
                            ->live()
                            ->afterStateUpdated(
                                fn(Set $set, Get $get) =>
                                static::generateRoomCode($set, $get)
                            )
                            ->options(function (Get $get) {
                                $dormId = $get('dorm_id');
                                if (! $dormId) {
                                    return [];
                                }

                                return Block::query()
                                    ->where('dorm_id', $dormId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->disabled(fn(Get $get) => blank($get('dorm_id')))
                            ->helperText('Pilih cabang terlebih dahulu untuk memuat daftar komplek.'),

                        // Tipe kamar (hanya yang aktif)
                        Select::make('room_type_id')
                            ->label('Tipe Kamar')
                            ->options(fn() => RoomType::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn(Set $set, Get $get) =>
                                static::generateRoomCode($set, $get)
                            ),

                        Forms\Components\TextInput::make('number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->maxLength(20)
                            ->live()
                            ->afterStateUpdated(
                                fn(Set $set, Get $get) =>
                                static::generateRoomCode($set, $get)
                            )
                            ->helperText('Contoh: 01, 02, 101, dst.'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kamar')
                            ->disabled()
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->dehydrated(true)
                            ->readonly(),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Kapasitas')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Boleh kosong, nanti bisa mengikuti kapasitas default dari tipe kamar.'),

                        Forms\Components\TextInput::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefix('Rp')
                            ->helperText('Boleh kosong, nanti bisa mengikuti tarif default dari tipe kamar.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Select::make('resident_category_id')
                            ->label('Kategori Kamar')
                            ->relationship('residentCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Kategori hanya bisa diubah jika kamar kosong (tidak ada penghuni aktif).')
                            ->disabled(function (?Room $record) {
                                // saat create, $record null => boleh pilih
                                if (! $record) return false;

                                // saat edit, jika ada penghuni aktif => disable
                                return ! $record->isEmpty();
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user  = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('block.dorm.name')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('block.name')
                    ->label('Komplek')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Tipe Kamar')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(fn() => Dorm::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->query(function (Builder $query, array $data) {
                        $dormId = $data['value'] ?? null;
                        if ($dormId) {
                            $query->whereHas('block', fn(Builder $q) => $q->where('dorm_id', $dormId));
                        }
                    })
                    ->visible(fn() => $user?->hasRole(['super_admin', 'main_admin'])),

                ...($user?->hasRole('super_admin')
                    ? [Tables\Filters\TrashedFilter::make()->label('Data Terhapus')]
                    : []),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                // Restore hanya super_admin
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) => $record?->trashed() && auth()->user()?->hasRole('super_admin')),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('super_admin')),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereHas('block.dorm');

        if ($user?->hasRole('super_admin')) {
            $query = $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        if ($user->hasRole('branch_admin')) {
            return $query->whereHas(
                'block',
                fn($q) => $q->whereIn('dorm_id', $user->branchDormIds())
            );
        }

        if ($user->hasRole('block_admin')) {
            return $query->whereIn('block_id', $user->blockIds());
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole([
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
        ]) ?? false;
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
        // soft delete only
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    protected static function generateRoomCode(\Filament\Forms\Set $set, \Filament\Forms\Get $get): void
    {
        $dormId     = $get('dorm_id');
        $blockId    = $get('block_id');
        $roomTypeId = $get('room_type_id');
        $number     = $get('number');

        if (! $dormId || ! $blockId || ! $roomTypeId || ! $number) {
            $set('code', null);
            return;
        }

        $dorm     = Dorm::find($dormId);
        $block    = Block::find($blockId);
        $roomType = RoomType::find($roomTypeId);

        if (! $dorm || ! $block || ! $roomType) {
            $set('code', null);
            return;
        }

        $code = \App\Models\Room::generateCode(
            $dorm->name,
            $block->name,
            $roomType->name,
            (string) $number
        );

        $set('code', $code);
    }
}
