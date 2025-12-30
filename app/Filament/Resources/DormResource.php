<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DormResource\Pages;
use App\Models\Dorm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
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
                    ->visible(function (Dorm $record) {
                        $user = auth()->user();

                        if (! $user?->hasRole(['super_admin', 'main_admin'])) {
                            return false;
                        }

                        // ✅ Data trashed tidak boleh diedit
                        if (method_exists($record, 'trashed') && $record->trashed()) {
                            return false;
                        }

                        return true;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (Dorm $record): bool =>
                            auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! $record->blocks()->exists()
                    ),

                /**
                 * ✅ Restore hanya jika tidak ada dorm aktif dengan nama sama.
                 */
                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn (Dorm $record): bool =>
                            auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    )
                    ->action(function (Dorm $record) {
                        $existsActiveSameName = Dorm::query()
                            ->where('name', $record->name)
                            ->whereNull('deleted_at')
                            ->exists();

                        if ($existsActiveSameName) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body("Tidak bisa memulihkan cabang \"{$record->name}\" karena sudah ada cabang aktif dengan nama yang sama.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->restore();

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Cabang \"{$record->name}\" berhasil dipulihkan.")
                            ->success()
                            ->send();
                    }),

                /**
                 * ✅ Force Delete (Hapus Permanen) — hanya super_admin dan hanya untuk data terhapus.
                 *    Tetap ditolak jika cabang masih punya komplek (blocks).
                 */
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn (Dorm $record): bool =>
                            auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Cabang')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen cabang ini? Data yang dihapus permanen tidak dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->before(function (Tables\Actions\ForceDeleteAction $action, Dorm $record) {
                        if ($record->blocks()->exists()) {
                            Notification::make()
                                ->title('Tidak dapat menghapus permanen')
                                ->body('Cabang yang memiliki komplek tidak dapat dihapus permanen. Hapus/lepaskan komplek terlebih dahulu.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                // Bulk Delete (Soft Delete) — hanya di tab aktif
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus')
                    ->visible(function ($livewire) {
                        $user = auth()->user();

                        if (! $user?->hasRole(['super_admin', 'main_admin'])) {
                            return false;
                        }

                        // ✅ Hanya tampil di tab aktif (bukan tab terhapus)
                        return ($livewire->activeTab ?? null) !== 'terhapus';
                    })
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
                                ->body("Berhasil menghapus {$deleted} cabang. Yang tidak bisa dihapus: " . $blocked->pluck('name')->unique()->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil menghapus {$deleted} cabang.")
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                /**
                 * ✅ Restore massal — hanya di tab terhapus.
                 *    Tolak yang namanya bentrok dengan dorm aktif.
                 */
                Tables\Actions\RestoreBulkAction::make()
                    ->label('Pulihkan')
                    ->visible(function ($livewire) {
                        $user = auth()->user();

                        if (! $user?->hasRole(['super_admin'])) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) === 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $restorable = collect();
                        $blocked    = collect();

                        foreach ($records as $record) {
                            /** @var Dorm $record */
                            if (! method_exists($record, 'trashed') || ! $record->trashed()) {
                                continue;
                            }

                            $existsActiveSameName = Dorm::query()
                                ->where('name', $record->name)
                                ->whereNull('deleted_at')
                                ->exists();

                            if ($existsActiveSameName) {
                                $blocked->push($record);
                            } else {
                                $restorable->push($record);
                            }
                        }

                        if ($restorable->isEmpty()) {
                            Notification::make()
                                ->title('Tidak Bisa Dipulihkan')
                                ->body('Semua data yang dipilih bentrok dengan nama cabang aktif, sehingga tidak bisa dipulihkan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        foreach ($restorable as $r) {
                            $r->restore();
                        }

                        $restoredCount = $restorable->count();

                        if ($blocked->isNotEmpty()) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil memulihkan {$restoredCount} cabang. Yang tidak bisa dipulihkan karena nama bentrok: " . $blocked->pluck('name')->unique()->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil memulihkan {$restoredCount} cabang.")
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // ✅ super_admin boleh lihat data terhapus
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        // main_admin tetap pakai SoftDeletingScope
        if ($user->hasRole('main_admin')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0');
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
        $user = auth()->user();

        if (! ($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
            return false;
        }

        // ✅ Kunci: trashed tidak bisa diedit
        if ($record && method_exists($record, 'trashed') && $record->trashed()) {
            return false;
        }

        return true;
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
            'index'  => Pages\ListDorms::route('/'),
            'create' => Pages\CreateDorm::route('/create'),
            'edit'   => Pages\EditDorm::route('/{record}/edit'),
        ];
    }
}
