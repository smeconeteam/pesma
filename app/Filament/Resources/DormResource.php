<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DormResource\Pages;
use App\Models\Dorm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DormResource extends Resource
{
    protected static ?string $model = Dorm::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?string $navigationGroupIcon = 'heroicon-o-home-group';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Cabang';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Cabang Asrama';
    protected static ?string $modelLabel = 'Cabang Asrama';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Cabang')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Cabang Asrama')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('is_active')
                            ->label('Status Aktif')
                            ->options([
                                1 => 'Aktif',
                                0 => 'Nonaktif',
                            ])
                            ->default(1)
                            ->required(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Cabang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin'])),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user && $user->hasRole(['super_admin', 'main_admin'])) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDorms::route('/'),
            'create' => Pages\CreateDorm::route('/create'),
            'edit'   => Pages\EditDorm::route('/{record}/edit'),
        ];
    }
}
