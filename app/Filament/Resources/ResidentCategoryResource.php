<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ResidentCategory;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentMethodBankAccount;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\Section as InfoSection;
use App\Filament\Resources\ResidentCategoryResource\Pages;

class ResidentCategoryResource extends Resource
{
    protected static ?string $model = ResidentCategory::class;

    protected static ?string $slug = 'kategori';
    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'Kategori';
    protected static ?string $pluralLabel = 'Kategori';
    protected static ?string $modelLabel = 'Kategori';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kategori')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: Pondok, Wisma, Asrama, Kos'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(65535)
                            ->helperText('Penjelasan singkat tentang kategori ini'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Rekening Bank untuk Pembayaran')
                    ->description('Pilih rekening bank yang akan digunakan untuk pembayaran kategori ini (opsional)')
                    ->schema([
                        Forms\Components\CheckboxList::make('bank_accounts')
                            ->label('Pilih Rekening Bank')
                            ->relationship(
                                'bankAccounts',
                                'account_holder'
                            )
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return "{$record->bank_name} - {$record->account_number} ({$record->account_name})";
                            })
                            ->options(function () {
                                return \App\Models\PaymentMethodBankAccount::query()
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        return [
                                            $account->id => "{$account->bank_name} - {$account->account_number} ({$account->account_name})"
                                        ];
                                    });
                            })
                            ->helperText('Rekening yang dipilih akan menjadi opsi pembayaran untuk kategori ini')
                            ->searchable()
                            ->bulkToggleable()
                            ->columnSpanFull()
                            ->visible(fn() => PaymentMethodBankAccount::where('is_active', true)->exists()),

                        Forms\Components\Placeholder::make('no_bank_accounts')
                            ->label('')
                            ->content('Belum ada rekening bank aktif. Silakan tambahkan rekening bank terlebih dahulu di menu Metode Pembayaran.')
                            ->visible(fn() => !PaymentMethodBankAccount::where('is_active', true)->exists()),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bankAccounts.account_holder')
                    ->label('Rekening Bank')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('-')
                    ->toggleable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('bankAccounts', function ($q) use ($search) {
                            $q->where('account_holder', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Jumlah Kamar')
                    ->counts('rooms')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(
                        fn(ResidentCategory $record): bool => (auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false)
                            && ! $record->trashed()
                    ),

                Tables\Actions\DeleteAction::make()
                    ->visible(function (ResidentCategory $record): bool {
                        $user = auth()->user();

                        if (!($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
                            return false;
                        }

                        if ($record->trashed()) {
                            return false;
                        }

                        if ($record->rooms()->exists()) {
                            return false;
                        }

                        return true;
                    })
                    ->action(function (ResidentCategory $record) {
                        if ($record->rooms()->exists()) {
                            Notification::make()
                                ->title('Tidak dapat menghapus')
                                ->body('Kategori ini masih digunakan oleh kamar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Berhasil menghapus kategori')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn(ResidentCategory $record): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $record->trashed()
                    )
                    ->action(function (ResidentCategory $record) {
                        $record->restore();

                        Notification::make()
                            ->title('Berhasil memulihkan kategori')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn(ResidentCategory $record): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $record->trashed()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Kategori')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen kategori ini? Data yang terhapus permanen tidak dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->before(function (Tables\Actions\ForceDeleteAction $action, ResidentCategory $record) {
                        if ($record->rooms()->exists()) {
                            Notification::make()
                                ->title('Tidak dapat menghapus permanen')
                                ->body('Kategori ini masih digunakan oleh kamar.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->action(function (ResidentCategory $record) {
                        $record->forceDelete();

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Kategori berhasil dihapus permanen.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus')
                    ->visible(function ($livewire): bool {
                        $user = auth()->user();

                        if (! ($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) !== 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $allowed = $records->filter(function (ResidentCategory $category) {
                            return ! $category->trashed() && ! $category->rooms()->exists();
                        });

                        $blocked = $records->diff($allowed);

                        if ($allowed->isEmpty()) {
                            Notification::make()
                                ->title('Aksi Dibatalkan')
                                ->body('Tidak ada kategori yang bisa dihapus. Kategori yang masih digunakan oleh kamar tidak dapat dihapus.')
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($allowed) {
                            foreach ($allowed as $category) {
                                $category->delete();
                            }
                        });

                        $deleted = $allowed->count();

                        if ($blocked->isNotEmpty()) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil menghapus {$deleted} kategori. Yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil menghapus {$deleted} kategori.")
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->label('Pulihkan')
                    ->visible(function ($livewire): bool {
                        $user = auth()->user();

                        if (! ($user?->hasRole('super_admin') ?? false)) {
                            return false;
                        }

                        return ($livewire->activeTab ?? null) === 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $restored = 0;

                        DB::transaction(function () use ($records, &$restored) {
                            foreach ($records as $category) {
                                if (!($category instanceof ResidentCategory) || !$category->trashed()) {
                                    continue;
                                }

                                $category->restore();
                                $restored++;
                            }
                        });

                        if ($restored === 0) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body('Tidak ada data yang bisa dipulihkan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Berhasil memulihkan {$restored} kategori.")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Informasi Kategori')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Kategori')
                            ->weight('medium'),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('-'),

                        TextEntry::make('rooms_count')
                            ->label('Jumlah Kamar')
                            ->state(fn(ResidentCategory $record) => $record->rooms()->count())
                            ->suffix(' kamar'),
                    ])
                    ->columns(2),

                InfoSection::make('Rekening Bank untuk Pembayaran')
                    ->schema([
                        TextEntry::make('bank_accounts_list')
                            ->label('Rekening yang Digunakan')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->state(function (ResidentCategory $record) {
                                $accounts = $record->bankAccounts()
                                    ->where('is_active', true)
                                    ->get();

                                if ($accounts->isEmpty()) {
                                    return ['Tidak ada rekening bank yang dipilih'];
                                }

                                return $accounts->map(function ($account) {
                                    return "{$account->bank_name} - {$account->account_number} ({$account->account_name})";
                                })->toArray();
                            })
                            ->placeholder('Tidak ada rekening bank yang dipilih'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                InfoSection::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diubah')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        if ($user?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole([
            'super_admin',
            'main_admin',
        ]) ?? false;
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
        if (!($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
            return false;
        }

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
        return static::canDelete(null);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListResidentCategories::route('/'),
            'create' => Pages\CreateResidentCategory::route('/buat'),
            'edit'   => Pages\EditResidentCategory::route('/{record}/edit'),
        ];
    }
}
