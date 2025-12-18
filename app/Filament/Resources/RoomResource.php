<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Filament\Resources\RoomResource\RelationManagers;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

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
                        Forms\Components\Select::make('dorm_id')
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
                                if ($record?->block?->dorm_id) {
                                    $component->state($record->block->dorm_id);
                                }
                            })
                            ->afterStateUpdated(function (Set $set) {
                                $set('block_id', null);
                                $set('code', null);
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
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

                        Forms\Components\Select::make('room_type_id')
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
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get)),

                        Forms\Components\TextInput::make('number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->maxLength(20)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
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
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

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
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Nonaktif',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('lokasi')
                    ->label('Lokasi')
                    ->form([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang')
                            ->options(function () {
                                $user = auth()->user();

                                $query = Dorm::query()
                                    ->where('is_active', true)
                                    ->whereNull('deleted_at')
                                    ->orderBy('name');

                                if ($user?->hasRole(['super_admin', 'main_admin'])) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user?->hasRole('branch_admin')) {
                                    return $query->whereIn('id', $user->branchDormIds())->pluck('name', 'id')->toArray();
                                }

                                if ($user?->hasRole('block_admin')) {
                                    return $query->whereHas('blocks', fn(Builder $q) => $q->whereIn('blocks.id', $user->blockIds()))
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }

                                return [];
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn(Set $set) => $set('block_id', null))
                            ->native(false),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->options(function (Get $get) {
                                $user = auth()->user();
                                $dormId = $get('dorm_id');

                                if (! $dormId) return [];

                                $query = Block::query()
                                    ->where('dorm_id', $dormId)
                                    ->whereNull('deleted_at')
                                    ->orderBy('name');

                                if ($user?->hasRole(['super_admin', 'main_admin'])) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user?->hasRole('branch_admin')) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user?->hasRole('block_admin')) {
                                    return $query->whereIn('id', $user->blockIds())->pluck('name', 'id')->toArray();
                                }

                                return [];
                            })
                            ->searchable()
                            ->disabled(fn(Get $get) => blank($get('dorm_id')))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['block_id'] ?? null, fn(Builder $q, $blockId) => $q->where('block_id', $blockId))
                            ->when(
                                ($data['dorm_id'] ?? null) && empty($data['block_id']),
                                fn(Builder $q) => $q->whereHas('block', fn(Builder $qb) => $qb->where('dorm_id', $data['dorm_id']))
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['dorm_id'])) {
                            $name = Dorm::query()->whereKey($data['dorm_id'])->value('name');
                            if ($name) $indicators[] = "Cabang: {$name}";
                        }
                        if (! empty($data['block_id'])) {
                            $name = Block::query()->whereKey($data['block_id'])->value('name');
                            if ($name) $indicators[] = "Komplek: {$name}";
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('created_at_range')
                    ->label('Tanggal Dibuat')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari')->native(false),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['created_from'])) $indicators[] = 'Dari: ' . $data['created_from'];
                        if (! empty($data['created_until'])) $indicators[] = 'Sampai: ' . $data['created_until'];
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn(Room $record): bool =>
                        auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! RoomResident::query()
                                ->where('room_id', $record->id)
                                ->whereNull('check_out_date')
                                ->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) => $record?->trashed() && auth()->user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']))
                        ->action(function (Collection $records) {
                            $allowed = $records->filter(function (Room $room) {
                                return ! RoomResident::query()
                                    ->where('room_id', $room->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                            });

                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada kamar yang bisa dihapus. Kamar yang masih memiliki penghuni aktif tidak dapat dihapus.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($allowed as $room) {
                                $room->delete();
                            }

                            $deleted = $allowed->count();

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Berhasil Sebagian')
                                    ->body("Berhasil menghapus {$deleted} kamar. Yang tidak bisa dihapus: " . $blocked->pluck('code')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} kamar.")
                                    ->success()
                                    ->send();
                            }
                        }),

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
