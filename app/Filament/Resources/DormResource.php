<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DormResource\Pages;
use App\Models\Dorm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class DormResource extends Resource
{
    protected static ?string $model = Dorm::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 1;

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
                            ->columnSpan(2)
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable(),

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
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (Dorm $record): bool =>
                            auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! $record->blocks()->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn (Dorm $record): bool =>
                            auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'main_admin']))
                        ->action(function (Collection $records) {
                            $allowed = $records->filter(fn (Dorm $r) => ! $r->blocks()->exists());
                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada cabang yang bisa dihapus. Cabang yang memiliki komplek tidak dapat dihapus.')
                                    ->danger()
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
                                    ->body("Berhasil menghapus {$deleted} cabang. Yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} cabang.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(['super_admin'])),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // ✅ super_admin boleh lihat/restore yang terhapus (TrashedFilter yang atur tampilan default)
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        // main_admin tetap pakai SoftDeletingScope (jadi yang terhapus tidak muncul)
        if ($user->hasRole('main_admin')) {
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
