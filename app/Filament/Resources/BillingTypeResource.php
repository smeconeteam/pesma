<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingTypeResource\Pages;
use App\Models\BillingType;
use App\Models\Dorm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingTypeResource extends Resource
{
    protected static ?string $model = BillingType::class;

    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Jenis Tagihan';
    protected static ?string $pluralLabel = 'Jenis Tagihan';
    protected static ?string $modelLabel = 'Jenis Tagihan';

    /** =========================
     *  ACCESS CONTROL (NO POLICY)
     *  ========================= */
    protected static function isAllowed(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('super_admin') || $user->hasRole('main_admin'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isAllowed();
    }
    public static function canViewAny(): bool
    {
        return static::isAllowed();
    }
    public static function canCreate(): bool
    {
        return static::isAllowed();
    }
    public static function canEdit($record): bool
    {
        return static::isAllowed();
    }
    public static function canDelete($record): bool
    {
        return static::isAllowed();
    }
    public static function canDeleteAny(): bool
    {
        return static::isAllowed();
    }

    /** =========================
     *  SOFT DELETE + EAGER LOAD
     *  ========================= */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // ✅ Hanya super_admin yang bisa lihat data terhapus
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['dorms:id,name']);
        }

        // ✅ main_admin hanya lihat data aktif (SoftDeletingScope default)
        return parent::getEloquentQuery()
            ->with(['dorms:id,name']);
    }

    /** =========================
     *  FORM
     *  ========================= */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Jenis Tagihan')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Tagihan')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Nama akan otomatis menjadi "Nama - Cabang" saat disimpan.'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('Rp'),

                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\Toggle::make('applies_to_all')
                        ->label('Berlaku untuk semua cabang')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ((bool) $state) {
                                // kosongkan pilihan cabang agar tidak nyangkut
                                $set('dorm_ids', []);
                                $set('dorm_id', null);
                            }
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('Cakupan Cabang')
                ->visible(fn (string $operation): bool => $operation !== 'view')
                ->schema([
                    // CREATE: boleh pilih banyak cabang
                    Forms\Components\Select::make('dorm_ids')
                        ->label('Cabang yang berlaku')
                        ->multiple()
                        ->options(
                            fn () => Dorm::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(
                            fn (Get $get, string $operation) =>
                                $operation === 'create' && ! (bool) $get('applies_to_all')
                        )
                        ->required(
                            fn (Get $get, string $operation) =>
                                $operation === 'create' && ! (bool) $get('applies_to_all')
                        )
                        ->helperText('Jika memilih beberapa cabang, sistem akan membuat data baru 1 per cabang (nominal sama).'),

                    // EDIT: wajib 1 cabang saja
                    Forms\Components\Select::make('dorm_id')
                        ->label('Cabang yang berlaku')
                        ->options(
                            fn () => Dorm::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(
                            fn (Get $get, string $operation) =>
                                $operation === 'edit' && ! (bool) $get('applies_to_all')
                        )
                        ->required(
                            fn (Get $get, string $operation) =>
                                $operation === 'edit' && ! (bool) $get('applies_to_all')
                        )
                        ->helperText('Saat edit, hanya boleh memilih 1 cabang.'),
                ]),
        ]);
    }

    /** =========================
     *  TABLE
     *  ========================= */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('cabang')
                    ->label('Cabang')
                    ->getStateUsing(function ($record): string {
                        if ($record->applies_to_all) {
                            return 'Semua Cabang';
                        }

                        return $record->dorms
                            ->pluck('name')
                            ->filter()
                            ->values()
                            ->implode(', ');
                    })
                    ->limit(50)
                    ->tooltip(function ($record): ?string {
                        if ($record->applies_to_all) {
                            return null;
                        }

                        $full = $record->dorms
                            ->pluck('name')
                            ->filter()
                            ->values()
                            ->implode(', ');

                        return $full ?: null;
                    })
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
                Tables\Filters\TernaryFilter::make('applies_to_all')->label('Semua Cabang'),

                SelectFilter::make('dorm_filter')
                    ->label('Cabang')
                    ->options(
                        fn () => Dorm::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        $dormId = $data['value'] ?? null;

                        if (! $dormId) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($dormId) {
                            $q->where('applies_to_all', true)
                                ->orWhereHas('dorms', fn (Builder $dq) => $dq->where('dorms.id', $dormId));
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->deleted_at === null),

                // ✅ Hapus (soft delete) hanya untuk data aktif
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($record) => $record->deleted_at === null),

                // ✅ Pulihkan hanya untuk data terhapus
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(fn ($record) => $record->deleted_at !== null),

                // ✅ Force delete hanya untuk data terhapus (tab data terhapus)
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(function ($record) {
                        $user = auth()->user();
                        if (! $user?->hasRole('super_admin')) {
                            return false;
                        }

                        return $record->deleted_at !== null;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Jenis Tagihan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen data ini? Data yang terhapus permanen tidak dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen'),
            ])
            // ✅ Bulk action: delete hanya aktif, restore hanya terhapus (tanpa grup)
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus')
                    ->visible(function ($livewire) {
                        $user = auth()->user();

                        if (! $user?->hasAnyRole(['super_admin', 'main_admin'])) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) !== 'terhapus';
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->label('Pulihkan')
                    ->visible(function ($livewire) {
                        $user = auth()->user();

                        if (! $user?->hasRole('super_admin')) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) === 'terhapus';
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBillingTypes::route('/'),
            'create' => Pages\CreateBillingType::route('/create'),
            'edit'   => Pages\EditBillingType::route('/{record}/edit'),
        ];
    }
}
