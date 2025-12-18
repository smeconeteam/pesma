<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingTypeResource\Pages;
use App\Models\BillingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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

        return $user
            && ($user->hasRole('super_admin') || $user->hasRole('main_admin'));
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['dorms:id,name']); // biar kolom cabang tidak N+1
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
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),

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
                ])
                ->columns(2),

            Forms\Components\Section::make('Cakupan Cabang')
                ->schema([
                    Forms\Components\Select::make('dorms')
                        ->label('Cabang yang berlaku')
                        ->multiple()
                        ->relationship(
                            name: 'dorms',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->where('is_active', true)
                                ->orderBy('name')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn (Get $get) => ! (bool) $get('applies_to_all'))
                        ->required(fn (Get $get) => ! (bool) $get('applies_to_all'))
                        ->helperText('Jika tidak berlaku untuk semua cabang, pilih cabang yang dituju.'),
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
                    ->money('IDR', locale: 'id')
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
                    ->limit(50) // potong jika kepanjangan
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

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
                Tables\Filters\TernaryFilter::make('applies_to_all')->label('Semua Cabang'),
                Tables\Filters\TrashedFilter::make()->label('Sampah'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),       // soft delete
                Tables\Actions\RestoreAction::make(),      // restore
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
