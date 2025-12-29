<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingTypeResource\Pages;
use App\Models\BillingType;
use App\Models\Dorm;
use App\Models\ResidentCategory;
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

    /** ACCESS CONTROL */
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

    /** SOFT DELETE + EAGER LOAD */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (!$user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['dorms:id,name', 'residentCategory:id,name']);
        }

        return parent::getEloquentQuery()
            ->with(['dorms:id,name', 'residentCategory:id,name']);
    }

    /** FORM */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Jenis Tagihan')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Tagihan')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Contoh: Biaya Kebersihan, Biaya Internet, dll.'),

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
                        ->label('Berlaku untuk semua cabang dan kategori')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ((bool) $state) {
                                $set('dorm_ids', []);
                                $set('dorm_id', null);
                                $set('resident_category_id', null);
                            }
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('Cakupan')
                ->visible(fn(Get $get) => !(bool) $get('applies_to_all'))
                ->schema([
                    Forms\Components\Select::make('resident_category_id')
                        ->label('Kategori Penghuni')
                        ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('Kosongkan jika berlaku untuk semua kategori')
                        ->columnSpanFull(),

                    // CREATE: boleh pilih banyak cabang
                    Forms\Components\Select::make('dorm_ids')
                        ->label('Cabang yang berlaku')
                        ->multiple()
                        ->options(fn() => Dorm::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn(Get $get, string $operation) => $operation === 'create')
                        ->required(fn(Get $get, string $operation) => $operation === 'create')
                        ->helperText('Jika memilih beberapa cabang, sistem akan membuat data baru 1 per cabang (nominal sama).'),

                    // EDIT: wajib 1 cabang saja
                    Forms\Components\Select::make('dorm_id')
                        ->label('Cabang yang berlaku')
                        ->options(fn() => Dorm::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn(Get $get, string $operation) => $operation === 'edit')
                        ->required(fn(Get $get, string $operation) => $operation === 'edit')
                        ->helperText('Saat edit, hanya boleh memilih 1 cabang.'),
                ])
                ->columns(2),
        ]);
    }

    /** TABLE */
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
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('residentCategory.name')
                    ->label('Kategori')
                    ->placeholder('Semua Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cabang')
                    ->label('Cabang')
                    ->getStateUsing(function ($record): string {
                        if ($record->applies_to_all) {
                            return 'Semua Cabang';
                        }

                        return $record->dorms->pluck('name')->filter()->values()->implode(', ') ?: '-';
                    })
                    ->limit(50)
                    ->tooltip(function ($record): ?string {
                        if ($record->applies_to_all) {
                            return null;
                        }

                        $full = $record->dorms->pluck('name')->filter()->values()->implode(', ');
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

                SelectFilter::make('resident_category_id')
                    ->label('Kategori Penghuni')
                    ->options(fn() => ResidentCategory::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('dorm_filter')
                    ->label('Cabang')
                    ->options(fn() => Dorm::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        $dormId = $data['value'] ?? null;
                        if (!$dormId) return $query;

                        return $query->where(function (Builder $q) use ($dormId) {
                            $q->where('applies_to_all', true)
                                ->orWhereHas('dorms', fn(Builder $dq) => $dq->where('dorms.id', $dormId));
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn($record) => $record->deleted_at === null),
                Tables\Actions\DeleteAction::make()->visible(fn($record) => $record->deleted_at === null),
                Tables\Actions\RestoreAction::make()->visible(fn($record) => $record->deleted_at !== null),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
