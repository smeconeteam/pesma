<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockResource\Pages;
use App\Filament\Resources\BlockResource\RelationManagers;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Unique;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Komplek';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Komplek Asrama';
    protected static ?string $modelLabel = 'Komplek Asrama';

    /**
     * ✅ Cek konflik restore:
     * Jika sudah ada data aktif (non-trashed) dengan dorm_id + name sama,
     * maka data yang terhapus tidak boleh dipulihkan.
     */
    protected static function hasActiveDuplicateForRestore(Block $record): bool
    {
        return Block::query()
            ->where('dorm_id', $record->dorm_id)
            ->where('name', $record->name)
            ->exists(); // default: hanya non-trashed (deleted_at NULL)
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Komplek')
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang Asrama')
                            ->relationship('dorm', 'name', function (Builder $query, ?Block $record) use ($user) {
                                $query->whereNull('deleted_at');

                                if (! $user) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                if ($user->hasRole(['super_admin', 'main_admin'])) {
                                    // Role filtering done, now handle active/inactive
                                } elseif ($user->hasRole('branch_admin')) {
                                    $dormIds = $user->branchDormIds();

                                    if ($dormIds && $dormIds->isNotEmpty()) {
                                        $query->whereIn('id', $dormIds);
                                    } else {
                                        $query->whereRaw('1 = 0');
                                        return;
                                    }
                                } else {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                // ✅ Saat EDIT: tampilkan yang aktif + yang sudah terpilih (meski nonaktif)
                                if ($record && $record->exists) {
                                    $query->where(function ($q) use ($record) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $record->dorm_id);
                                    });
                                } else {
                                    // ✅ Saat CREATE: hanya yang aktif
                                    $query->where('is_active', true);
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn ($record) => $record && $record->rooms()->exists())
                            ->dehydrated(fn ($record) => ! ($record && $record->rooms()->exists()))
                            ->helperText(
                                fn ($record) =>
                                    $record && $record->rooms()->exists()
                                        ? 'Cabang tidak dapat diubah karena komplek ini sudah memiliki kamar.'
                                        : null
                            ),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Komplek')
                            ->required()
                            ->maxLength(255)
                            /**
                             * ✅ Aturan unik:
                             * - Unik PER dorm_id (beda cabang boleh sama)
                             * - Hanya dibandingkan dengan data yang belum terhapus (deleted_at NULL)
                             * - Saat edit: ignore record aktif (ignoreRecord: true)
                             */
                            ->unique(
                                table: Block::class,
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                    return $rule
                                        ->where('dorm_id', $get('dorm_id'))
                                        ->whereNull('deleted_at');
                                }
                            ),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpan(2)
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
        $user  = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dorm.name')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Komplek')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40)
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

                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->relationship(
                        'dorm',
                        'name',
                        fn (Builder $query) => $query
                            ->whereNull('deleted_at')   // ✅ tetap sembunyikan yang soft-deleted
                            ->orderBy('name')           // ✅ non-aktif ikut tampil (tidak filter is_active)
                    )
                    ->visible(fn () => $user?->hasRole(['super_admin', 'main_admin']))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(function (Block $record) {
                        $user = auth()->user();

                        if (! $user?->hasRole(['super_admin', 'main_admin'])) {
                            return false;
                        }

                        if (method_exists($record, 'trashed') && $record->trashed()) {
                            return false;
                        }

                        return true;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (Block $record): bool =>
                            auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! $record->rooms()->exists()
                    ),

                /**
                 * ✅ Restore diblok kalau ada duplikat aktif dengan dorm_id + name sama
                 */
                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn (Block $record): bool =>
                            auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    )
                    ->disabled(fn (Block $record): bool => static::hasActiveDuplicateForRestore($record))
                    ->tooltip(function (Block $record): ?string {
                        if (! static::hasActiveDuplicateForRestore($record)) return null;

                        return 'Tidak bisa dipulihkan karena sudah ada komplek aktif dengan nama yang sama pada cabang ini.';
                    })
                    ->action(function (Block $record): void {
                        if (static::hasActiveDuplicateForRestore($record)) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body('Tidak bisa dipulihkan karena sudah ada komplek aktif dengan nama yang sama pada cabang ini.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->restore();

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Data komplek berhasil dipulihkan.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                        ->action(function (Collection $records) {

                            $allowed = $records->filter(fn (Block $r) => ! $r->rooms()->exists());
                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada komplek yang bisa dihapus. Komplek yang memiliki kamar tidak dapat dihapus.')
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
                                    ->body("Berhasil menghapus {$deleted} komplek. Yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} komplek.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    /**
                     * ✅ Restore bulk diblok kalau ada duplikat aktif (dorm_id + name sama)
                     */
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                        ->action(function (Collection $records): void {
                            $allowed = $records->filter(fn (Block $r) => ! static::hasActiveDuplicateForRestore($r));
                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Gagal Memulihkan')
                                    ->body('Semua data yang dipilih tidak bisa dipulihkan karena sudah ada duplikat aktif pada cabang yang sama.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($allowed as $r) {
                                $r->restore();
                            }

                            $restored = $allowed->count();

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Berhasil Sebagian')
                                    ->body("Berhasil memulihkan {$restored} komplek. Yang tidak bisa dipulihkan karena duplikat: " . $blocked->pluck('name')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil memulihkan {$restored} komplek.")
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    /**
     * Scope data berdasarkan role & admin_scopes.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereHas('dorm');

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();

            if ($dormIds && $dormIds->isNotEmpty()) {
                return $query->whereIn('dorm_id', $dormIds);
            }

            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();

            if ($blockIds && $blockIds->isNotEmpty()) {
                return $query->whereIn('id', $blockIds);
            }

            return $query->whereRaw('1 = 0');
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
        $user = auth()->user();
        return $user?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! $user?->hasRole(['super_admin', 'main_admin'])) {
            return false;
        }

        if (method_exists($record, 'trashed') && $record->trashed()) {
            return false;
        }

        return true;
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
            'index'  => Pages\ListBlocks::route('/'),
            'create' => Pages\CreateBlock::route('/create'),
            'edit'   => Pages\EditBlock::route('/{record}/edit'),
        ];
    }
}
