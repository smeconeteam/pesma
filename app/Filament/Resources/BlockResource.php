<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockResource\Pages;
use App\Filament\Resources\BlockResource\RelationManagers;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Komplek';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Komplek Asrama';
    protected static ?string $modelLabel = 'Komplek Asrama';

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Komplek')
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang Asrama')
                            ->relationship('dorm', 'name', function (Builder $query) use ($user) {
                                $query->where('is_active', true)
                                    ->whereNull('deleted_at');

                                if (! $user) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                if ($user->hasRole(['super_admin', 'main_admin'])) {
                                    return;
                                }

                                if ($user->hasRole('branch_admin')) {
                                    $dormIds = $user->branchDormIds();

                                    if ($dormIds && $dormIds->isNotEmpty()) {
                                        $query->whereIn('id', $dormIds);
                                    } else {
                                        $query->whereRaw('1 = 0');
                                    }
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Komplek')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

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
                        fn(Builder $query) =>
                        $query->where('is_active', true)
                            ->whereNull('deleted_at')
                    )
                    ->visible(fn() => $user?->hasRole(['super_admin', 'main_admin']))
                    ->native(false),

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
                    ->visible(fn() => auth()->user()?->hasRole([
                        'super_admin',
                        'main_admin',
                    ])),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn(Block $record): bool =>
                        auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! $record->rooms()->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn(Block $record): bool =>
                        auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin']))
                        ->action(function (Collection $records) {

                            $allowed = $records->filter(fn(Block $r) => ! $r->rooms()->exists());
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
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('super_admin')),
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

        // resident dan lainnya: tidak boleh lihat
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
            'index'  => Pages\ListBlocks::route('/'),
            'create' => Pages\CreateBlock::route('/create'),
            'edit'   => Pages\EditBlock::route('/{record}/edit'),
        ];
    }
}
