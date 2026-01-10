<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Models\Block;
use App\Models\Contact;
use App\Models\Dorm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Kontak';
    protected static ?string $pluralLabel = 'Kontak';
    protected static ?string $modelLabel = 'Kontak';

    private const ALL_BRANCHES = '__ALL__';         // sentinel form
    private const FILTER_ALL_BRANCHES = '__NULL__'; // sentinel filter (NULL dorm_id)

    /** ====== ROLE HELPERS ====== */
    private static function user()
    {
        return auth()->user();
    }

    private static function hasAnyRole(array $roles): bool
    {
        $user = self::user();
        if (! $user) return false;

        foreach ($roles as $role) {
            if ($user->hasRole($role)) return true;
        }

        return false;
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasAnyRole(['super_admin']);
    }

    public static function canManage(): bool
    {
        // super + main boleh create/edit/delete
        return self::hasAnyRole(['super_admin', 'main_admin']);
    }

    public static function canSeeTrashed(): bool
    {
        // hanya super admin
        return self::isSuperAdmin();
    }

    public static function canUseResource(): bool
    {
        // semua admin boleh akses list/view
        return self::hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']);
    }

    /** ====== SCOPE: admin cabang/komplek ====== */
    public static function getScopedDormIds(): ?array
    {
        // super & main: semua
        if (self::hasAnyRole(['super_admin', 'main_admin'])) {
            return null;
        }

        $user = self::user();
        if (! $user) return [];

        $scopes = method_exists($user, 'adminScopes') ? $user->adminScopes()->get() : collect();

        $dormIds = [];

        foreach ($scopes as $scope) {
            if (! empty($scope->dorm_id)) {
                $dormIds[] = (int) $scope->dorm_id;
                continue;
            }

            if (! empty($scope->block_id)) {
                $blockDormId = Block::query()->whereKey($scope->block_id)->value('dorm_id');
                if ($blockDormId) {
                    $dormIds[] = (int) $blockDormId;
                }
            }
        }

        return array_values(array_unique(array_filter($dormIds)));
    }

    /** ====== Permissions (tanpa policy) ====== */
    public static function canAccess(): bool { return self::canUseResource(); }
    public static function shouldRegisterNavigation(): bool { return self::canUseResource(); }
    public static function canViewAny(): bool { return self::canUseResource(); }
    public static function canCreate(): bool { return self::canManage(); }

    public static function canView(Model $record): bool
    {
        if ($record instanceof Contact && $record->trashed() && ! self::canSeeTrashed()) {
            return false;
        }

        return self::canUseResource();
    }

    public static function canEdit(Model $record): bool { return self::canManage(); }
    public static function canDelete(Model $record): bool { return self::canManage(); }

    /** ====== Query base ====== */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // hanya super boleh lihat trashed
        if (self::canSeeTrashed()) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        // scope cabang/komplek: hanya cabangnya + semua cabang (NULL)
        $scopedDormIds = self::getScopedDormIds();

        if (is_array($scopedDormIds)) {
            $query->where(function (Builder $q) use ($scopedDormIds) {
                $q->whereNull('dorm_id');

                if (count($scopedDormIds) > 0) {
                    $q->orWhereIn('dorm_id', $scopedDormIds);
                }
            });
        }

        return $query;
    }

    /** ====== Helpers ====== */
    public static function normalizeDormId($state): ?int
    {
        if ($state === self::ALL_BRANCHES || blank($state)) {
            return null;
        }

        return (int) $state;
    }

    /**
     * Format: "Nama (Cabang X)" atau "Nama (Semua Cabang)"
     */
    public static function buildDisplayName(?string $name, $dormId): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '';
        }

        if (blank($dormId) || $dormId === self::ALL_BRANCHES) {
            return "{$name} (Semua Cabang)";
        }

        $dormName = Dorm::query()->whereKey($dormId)->value('name') ?: 'Cabang';

        return "{$name} ({$dormName})";
    }

    /**
     * ✅ Rule baru:
     * Tidak boleh ada data AKTIF (deleted_at null) dengan name + dorm_id sama.
     */
    public static function hasActiveDuplicateByNameDorm(string $name, ?int $dormId, ?int $ignoreId = null): bool
    {
        $q = Contact::query()
            ->withoutTrashed()
            ->where('name', $name);

        if ($dormId === null) {
            $q->whereNull('dorm_id');
        } else {
            $q->where('dorm_id', $dormId);
        }

        if ($ignoreId) {
            $q->whereKeyNot($ignoreId);
        }

        return $q->exists();
    }

    /**
     * Dipakai untuk blok restore (cek duplikat aktif name+dorm).
     */
    public static function hasActiveDuplicate(Contact $record): bool
    {
        return self::hasActiveDuplicateByNameDorm(
            (string) $record->name,
            $record->dorm_id ? (int) $record->dorm_id : null,
            null
        );
    }

    /**
     * Dipakai di Create/Edit agar errornya jadi validation error, bukan SQL error.
     */
    public static function ensureUniqueActiveNameDorm(array $data, ?int $ignoreId = null): void
    {
        $name   = trim((string) ($data['name'] ?? ''));
        $dormId = $data['dorm_id'] ?? null; // sudah null untuk semua cabang karena dehydrateStateUsing
        $dormId = $dormId === null ? null : (int) $dormId;

        if ($name === '') {
            return;
        }

        if (self::hasActiveDuplicateByNameDorm($name, $dormId, $ignoreId)) {
            $cabang = $dormId
                ? (Dorm::query()->whereKey($dormId)->value('name') ?: 'Cabang')
                : 'Semua Cabang';

            throw ValidationException::withMessages([
                'name' => "Nama kontak \"{$name}\" sudah digunakan untuk {$cabang} (data aktif).",
            ]);
        }
    }

    public static function getDormFilterOptions(): array
    {
        $base = [self::FILTER_ALL_BRANCHES => 'Semua Cabang'];

        $scopedDormIds = self::getScopedDormIds();
        if (is_array($scopedDormIds)) {
            if (count($scopedDormIds) === 0) {
                return $base;
            }

            $dorms = Dorm::query()
                ->whereIn('id', $scopedDormIds)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();

            return $base + $dorms;
        }

        $dorms = Dorm::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $base + $dorms;
    }

    /** ====== Form ====== */
    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kontak')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $set('display_name', self::buildDisplayName($state, $get('dorm_id')));
                    })
                    ->disabled(fn () => ! self::canManage()),

                Forms\Components\TextInput::make('phone')
                    ->label('Nomor')
                    ->tel()
                    ->required()
                    ->maxLength(30)
                    ->disabled(fn () => ! self::canManage()),

                Forms\Components\Select::make('dorm_id')
                    ->label('Cabang')
                    ->helperText('Pilih "Semua Cabang" jika kontak ini berlaku untuk semua cabang.')
                    ->options(function () {
                        $dorms = Dorm::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();

                        return [self::ALL_BRANCHES => 'Semua Cabang'] + $dorms;
                    })
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->default(self::ALL_BRANCHES)
                    ->afterStateHydrated(function (Set $set, $state) {
                        $set('dorm_id', $state ?? self::ALL_BRANCHES);
                    })
                    ->dehydrateStateUsing(fn ($state) => $state === self::ALL_BRANCHES ? null : $state)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $set('display_name', self::buildDisplayName($get('name'), $state));
                    })
                    ->disabled(fn () => ! self::canManage()),

                Forms\Components\TextInput::make('display_name')
                    ->label('Nama (Otomatis)')
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->disabled(fn () => ! self::canManage()),

                Forms\Components\Textarea::make('auto_message')
                    ->label('Pesan Otomatis (Opsional)')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull()
                    ->disabled(fn () => ! self::canManage()),
            ]);
    }

    /** ====== Infolist (ViewAction) ====== */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Detail Kontak')
                ->schema([
                    TextEntry::make('display_name')->label('Nama'),
                    TextEntry::make('phone')->label('Nomor'),

                    TextEntry::make('dorm.name')
                        ->label('Cabang')
                        ->getStateUsing(fn (Contact $record) => $record->dorm?->name ?? 'Semua Cabang'),

                    TextEntry::make('is_active')
                        ->label('Status')
                        ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Nonaktif'),

                    TextEntry::make('auto_message')
                        ->label('Pesan Otomatis')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    private static function isTrashTab(ListContacts $livewire): bool
    {
        return (string) ($livewire->activeTab ?? 'aktif') === 'terhapus';
    }

    /** ====== Table ====== */
    public static function table(Table $table): Table
    {
        return $table
            ->filtersFormColumns(1)
            ->persistFiltersInSession()
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dorm.name')
                    ->label('Cabang')
                    ->getStateUsing(fn (Contact $record) => $record->dorm?->name ?? 'Semua Cabang')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('auto_message')
                    ->label('Pesan Otomatis')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ])
                    ->native(false)
                    ->indicateUsing(function (array $data): array {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') return [];

                        return [
                            Indicator::make('Status: ' . ((string) $value === '1' ? 'Aktif' : 'Nonaktif')),
                        ];
                    })
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') return $query;

                        return $query->where('is_active', (bool) ((int) $value));
                    }),

                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(fn () => self::getDormFilterOptions())
                    ->searchable()
                    ->native(false)
                    ->indicateUsing(function (array $data): array {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') return [];

                        if ($value === self::FILTER_ALL_BRANCHES) {
                            return [Indicator::make('Cabang: Semua Cabang')];
                        }

                        $name = Dorm::query()->whereKey($value)->value('name') ?: 'Cabang';
                        return [Indicator::make("Cabang: {$name}")];
                    })
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') return $query;

                        if ($value === self::FILTER_ALL_BRANCHES) {
                            return $query->whereNull('dorm_id');
                        }

                        return $query->where('dorm_id', $value);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->infolist(fn (Infolist $infolist) => self::infolist($infolist)),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Contact $record) => self::canManage() && ! $record->trashed()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Contact $record) => self::canManage() && ! $record->trashed()),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn (Contact $record) => self::canSeeTrashed() && $record->trashed())
                    ->disabled(fn (Contact $record) => self::hasActiveDuplicate($record))
                    ->tooltip(fn (Contact $record) => self::hasActiveDuplicate($record)
                        ? 'Tidak bisa dipulihkan karena sudah ada data aktif dengan Nama + Cabang yang sama.'
                        : null
                    ),
            ])

            /**
             * ✅ INI yang bikin checkbox selection muncul (mirip DiscountResource)
             * - Tab Aktif: Bulk delete (main/super)
             * - Tab Terhapus: Bulk restore (super)
             */
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->visible(fn (ListContacts $livewire) => self::canManage() && ! self::isTrashTab($livewire))
                    ->modalHeading('Hapus Data Terpilih')
                    ->modalDescription('Data akan dipindahkan ke tempat sampah.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('restore_selected')
                    ->label('Pulihkan Terpilih')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (ListContacts $livewire) => self::canSeeTrashed() && self::isTrashTab($livewire))
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan Data Terpilih')
                    ->modalDescription('Data yang memiliki duplikat aktif (Nama + Cabang sama) akan dilewati.')
                    ->modalSubmitActionLabel('Ya, Pulihkan')
                    ->action(function (Collection $records) {
                        $restored = 0;
                        $skipped  = 0;

                        foreach ($records as $record) {
                            /** @var Contact $record */
                            if (self::hasActiveDuplicate($record)) {
                                $skipped++;
                                continue;
                            }

                            $record->restore();
                            $restored++;
                        }

                        if ($restored > 0 && $skipped === 0) {
                            Notification::make()
                                ->title('Berhasil Dipulihkan')
                                ->body("Berhasil memulihkan {$restored} data.")
                                ->success()
                                ->send();
                        } elseif ($restored > 0 && $skipped > 0) {
                            Notification::make()
                                ->title('Selesai dengan Peringatan')
                                ->body("Dipulihkan: {$restored}. Dilewati (duplikat aktif): {$skipped}.")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Data yang Dipulihkan')
                                ->body("Semua data yang dipilih memiliki duplikat aktif.")
                                ->warning()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('display_name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
