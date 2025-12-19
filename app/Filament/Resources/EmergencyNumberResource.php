<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmergencyNumberResource\Pages;
use App\Filament\Resources\EmergencyNumberResource\RelationManagers;
use App\Models\EmergencyNumber;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmergencyNumberResource extends Resource
{
    protected static ?string $model = EmergencyNumber::class;

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Nomor Darurat';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Nomor Darurat';
    protected static ?string $modelLabel = 'Nomor Darurat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->label('Nama')
                    ->maxLength(255),

                TextInput::make('phone_number')
                    ->required()
                    ->label('Nomor Telepon')
                    ->tel()
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->label('Nomor Telepon')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmergencyNumbers::route('/'),
            'create' => Pages\CreateEmergencyNumber::route('/create'),
            'edit' => Pages\EditEmergencyNumber::route('/{record}/edit'),
        ];
    }
}
