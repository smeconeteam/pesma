<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomTypeResource\Pages;
use App\Filament\Resources\RoomTypeResource\RelationManagers;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Tipe Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Tipe Kamar';
    protected static ?string $modelLabel = 'Tipe Kamar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tipe Kamar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tipe')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('default_capacity')
                            ->label('Kapasitas Default')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Jumlah penghuni default dalam satu kamar.'),

                        Forms\Components\TextInput::make('default_monthly_rate')
                            ->label('Tarif Bulanan Default')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Rp'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tipe')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('default_capacity')
                    ->label('Kapasitas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true) // tampil sebagai Rupiah
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
                    ]),

                ...(auth()->user()?->hasRole('super_admin')
                    ? [Tables\Filters\TrashedFilter::make()->label('Data Terhapus')]
                    : []),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasRole([
                        'super_admin',
                        'main_admin',
                    ])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasRole([
                        'super_admin',
                        'main_admin',
                    ])),

                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn(RoomType $record): bool =>
                        auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole([
                            'super_admin',
                            'main_admin',
                        ])),
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

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        // Hanya super_admin & main_admin yang bisa melihat & mengelola tipe kamar
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
        return static::canDelete(null);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit'   => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}
