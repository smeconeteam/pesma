<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomRuleResource\Pages;
use App\Models\RoomRule;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Services\IconService;

class RoomRuleResource extends Resource
{
    protected static ?string $model = RoomRule::class;

    protected static ?string $slug = 'peraturan-kamar';

    protected static ?string $navigationGroup = 'Asrama';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Peraturan Kamar';

    protected static ?string $pluralLabel = 'Peraturan Kamar';

    protected static ?string $modelLabel = 'Peraturan Kamar';

   public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Peraturan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Peraturan')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('slug', \Illuminate\Support\Str::slug($state));
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn($rule) => $rule->whereNull('deleted_at')
                            )
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis: nama peraturan (contoh: dilarang-merokok)')
                            ->columnSpan(1),

                        Forms\Components\Group::make([
                            Forms\Components\Placeholder::make('icon_grid_styles')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <style>
                                        .icon-grid-selector .fi-fo-select-option-content {
                                            display: grid !important;
                                            grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
                                            gap: 0.5rem !important;
                                            padding: 0.75rem !important;
                                            width: 100% !important;
                                        }
                                        .icon-grid-selector .fi-fo-select-option {
                                            padding: 0 !important;
                                        }
                                        .icon-grid-selector .fi-fo-select-option > span {
                                            display: none !important;
                                        }
                                    </style>
                                '))
                                ->columnSpan(0),

                            Forms\Components\Select::make('icon')
                                ->label('Ikon')
                                ->options(static::getIconOptions())
                                ->searchable()
                                ->native(false)
                                ->allowHtml()
                                ->extraAttributes(['class' => 'icon-grid-selector'])
                                ->getSearchResultsUsing(function (string $search) {
                                    if (empty($search)) {
                                        return static::getIconOptions();
                                    }
                                    
                                    $icons = IconService::getAllIcons();
                                    
                                    return collect($icons)
                                        ->filter(fn ($label, $icon) => 
                                            stripos($label, $search) !== false || 
                                            stripos($icon, $search) !== false
                                        )
                                        ->mapWithKeys(fn ($label, $icon) => [
                                            $icon => '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                                                svg($icon, 'w-4 h-4')->toHtml() .
                                                '<span class="sr-only">' . $label . '</span>' .
                                                '</div>'
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    if (!$value) return null;
                                    $label = IconService::getAllIcons()[$value] ?? $value;
                                    $svgHtml = '';
                                    try {
                                        $svgHtml = svg($value, 'w-5 h-5')->toHtml();
                                    } catch (\Exception $e) {
                                        $svgHtml = '';
                                    }

                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2">' .
                                        $svgHtml .
                                        '<span>' . $label . '</span>' .
                                        '</div>'
                                    );
                                })
                                ->columnSpan(1),
                        ])->columns(1)->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kamar yang Diberlakukan')
                    ->description('Pilih kamar yang akan diberlakukan peraturan ini.')
                    ->schema([
                        Forms\Components\Select::make('rooms')
                            ->label('Kamar')
                            ->relationship('rooms', 'id')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->code . ' - ' . ($record->block?->dorm?->name ?? '') . ' ' . ($record->block?->name ?? ''))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(fn ($record, $livewire) => ($livewire->activeTab === 'terhapus' || $record->trashed()) ? 'view' : 'edit')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peraturan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ViewColumn::make('icon')
                    ->label('Ikon')
                    ->view('filament.columns.icon-with-label'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading(fn ($record) => 'Detail: ' . $record->name)
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Detail Peraturan')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Peraturan'),
                                        TextEntry::make('slug')
                                            ->label('Slug')
                                            ->copyable(),
                                        TextEntry::make('icon')
                                            ->label('Icon')
                                            ->formatStateUsing(function ($state) {
                                                $labels = IconService::getAllIcons();
                                                $label = $labels[$state] ?? $state;
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex items-center gap-2">' .
                                                    ($state ? svg($state, 'w-5 h-5')->toHtml() : '') .
                                                    '<span>' . $label . '</span></div>'
                                                );
                                            }),
                                        IconEntry::make('is_active')
                                            ->label('Status Aktif')
                                            ->boolean(),
                                        TextEntry::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->dateTime('d M Y H:i'),
                                        TextEntry::make('deleted_at')
                                            ->label('Dihapus Pada')
                                            ->dateTime('d M Y H:i')
                                            ->placeholder('-'),
                                    ]),
                            ]),
                    ]),

                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) !== 'terhapus'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) !== 'terhapus')
                    ->action(function (RoomRule $record) {
                        $record->delete();

                        Notification::make()
                            ->title('Peraturan berhasil dihapus')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->successNotificationTitle('Peraturan berhasil dihapus permanen')
                    ->visible(fn ($livewire) => auth()->user()->hasRole('super_admin') && ($livewire->activeTab ?? null) === 'terhapus'),
                    
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(fn ($livewire) => auth()->user()->hasRole('super_admin') && ($livewire->activeTab ?? null) === 'terhapus')
                    ->action(function (RoomRule $record) {
                        $targetSlug = $record->slug;
                        // Support legacy slug with __trashed__
                        if (\Illuminate\Support\Str::contains($targetSlug, '__trashed__')) {
                            $targetSlug = \Illuminate\Support\Str::before($targetSlug, '__trashed__');
                        }

                        $existsActive = RoomRule::query()
                            ->whereNull('deleted_at')
                            ->where('slug', $targetSlug)
                            ->where('id', '!=', $record->id)
                            ->exists();

                        if ($existsActive) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body("Tidak bisa memulihkan karena sudah ada peraturan aktif dengan slug: {$targetSlug}.")
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($targetSlug !== $record->slug) {
                             $record->updateQuietly(['slug' => $targetSlug]);
                        }
                        
                        $record->restore();

                        Notification::make()
                            ->title('Peraturan berhasil dipulihkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus yang dipilih')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) !== 'terhapus')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->delete();
                        }

                        Notification::make()
                            ->title('Peraturan terpilih berhasil dihapus')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->label('Pulihkan yang dipilih')
                    ->visible(fn ($livewire) => auth()->user()->hasRole('super_admin') && ($livewire->activeTab ?? null) === 'terhapus')
                    ->action(function (Collection $records) {
                        $restored = 0;
                        $blockedSlugs = [];

                        DB::transaction(function () use ($records, &$restored, &$blockedSlugs) {
                            foreach ($records as $record) {
                                if (!($record instanceof RoomRule) || !$record->trashed()) {
                                    continue;
                                }

                                $targetSlug = $record->slug;
                                if (\Illuminate\Support\Str::contains($targetSlug, '__trashed__')) {
                                    $targetSlug = \Illuminate\Support\Str::before($targetSlug, '__trashed__');
                                }

                                $existsActive = RoomRule::query()
                                    ->whereNull('deleted_at')
                                    ->where('slug', $targetSlug)
                                    ->where('id', '!=', $record->id)
                                    ->exists();

                                if ($existsActive) {
                                    $blockedSlugs[] = $targetSlug;
                                    continue;
                                }

                                if ($targetSlug !== $record->slug) {
                                    $record->updateQuietly(['slug' => $targetSlug]);
                                }
                                $record->restore();
                                $restored++;
                            }
                        });

                        if ($restored === 0) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body('Tidak ada data yang bisa dipulihkan karena semua slug sudah digunakan oleh data aktif.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!empty($blockedSlugs)) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil memulihkan {$restored} peraturan. Gagal karena slug sudah dipakai: " . implode(', ', array_unique($blockedSlugs)))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil memulihkan {$restored} peraturan.")
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomRules::route('/'),
            'create' => Pages\CreateRoomRule::route('/create'),
            'edit' => Pages\EditRoomRule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['main_admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['main_admin', 'super_admin']);
    }

    public static function canEdit($record): bool
    {
        // Prevent editing trashed records
        if ($record->trashed()) {
            return false;
        }
        
        return auth()->user()->hasRole(['main_admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasRole(['main_admin', 'super_admin']);
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canRestore($record): bool
    {
        return auth()->user()->hasRole('super_admin');
    }
    
    public static function getAvailableIcons(): array
    {
        return IconService::getAllIcons();
    }

    public static function getIconOptions(): array
    {
        return collect(IconService::getAllIcons())
            ->mapWithKeys(function ($label, $icon) {
                $svgHtml = '';
                try {
                    $svgHtml = svg($icon, 'w-4 h-4')->toHtml();
                } catch (\Exception $e) {
                    $svgHtml = '';
                }

                return [$icon => '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                    $svgHtml .
                    '<span class="sr-only">' . $label . '</span>' .
                    '</div>'
                ];
            })
            ->toArray();
    }

}
