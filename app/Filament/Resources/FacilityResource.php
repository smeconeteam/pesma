<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
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

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;

    protected static ?string $slug = 'fasilitas';

    protected static ?string $navigationGroup = 'Asrama';

    protected static ?string $navigationLabel = 'Fasilitas';

    protected static ?int $navigationSort = 7;

    protected static ?string $pluralLabel = 'Fasilitas';

   public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Fasilitas')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Fasilitas')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $type = $get('type');
                                if ($type && $state) {
                                    $set('slug', \Illuminate\Support\Str::slug($type . '-' . $state));
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipe Fasilitas')
                            ->required()
                            ->options([
                                'parkir' => 'Parkir',
                                'umum' => 'Umum',
                                'kamar_mandi' => 'Kamar Mandi',
                                'kamar' => 'Kamar',
                            ])
                            ->native(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $name = $get('name');
                                if ($name && $state) {
                                    $set('slug', \Illuminate\Support\Str::slug($state . '-' . $name));
                                }
                            })
                            ->columnSpan(1),

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
                            ->helperText('Otomatis: tipe-nama (contoh: parkir-motor)')
                            ->columnSpan(1),

                        Forms\Components\Group::make([
                            Forms\Components\Select::make('icon')
                                ->label('Ikon')
                                ->searchable()
                                ->allowHtml()
                                ->required()
                                ->native(false)
                                ->live()
                                ->columnSpan(1)
                                ->options(static::getIconOptions())
                                ->getSearchResultsUsing(function (string $search) {
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
                                ->getOptionLabelUsing(fn ($value) => 
                                    new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2 text-sm">' .
                                        ($value ? svg($value, 'w-5 h-5')->toHtml() : '') .
                                        '<span>' . (IconService::getAllIcons()[$value] ?? $value) . '</span>' .
                                        '</div>'
                                    )
                                )
                                ->extraAttributes([
                                    'class' => 'icon-grid-selector',
                                ]),

                            Forms\Components\Placeholder::make('icon_grid_style')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <style>
                                        /* Target the parent container to allow grid dropdown */
                                        .icon-grid-selector > div > div {
                                            position: relative;
                                        }
                                        /* Target the dropdown list list */
                                        .icon-grid-selector .fi-select-input-options-list ul,
                                        .icon-grid-selector .choices__list--dropdown .choices__list {
                                            display: grid !important;
                                            grid-template-columns: repeat(7, 1fr) !important;
                                            gap: 4px !important;
                                            padding: 8px !important;
                                            max-height: 400px !important;
                                            overflow-y: auto !important;
                                        }
                                        /* Style individual grid items */
                                        .icon-grid-selector .fi-select-input-option,
                                        .icon-grid-selector .choices__item--choice {
                                            padding: 0 !important;
                                            display: flex !important;
                                            aspect-ratio: 1/1 !important;
                                            align-items: center !important;
                                            justify-content: center !important;
                                            border-radius: 6px !important;
                                            border: 1px dashed transparent !important;
                                            transition: all 0.2s !important;
                                        }
                                        .icon-grid-selector .fi-select-input-option:hover,
                                        .icon-grid-selector .choices__item--choice:hover {
                                            background-color: rgba(var(--primary-500), 0.1) !important;
                                            border-color: rgba(var(--primary-500), 0.5) !important;
                                        }
                                        /* Ensure the search container stays full width above the grid */
                                        .icon-grid-selector .fi-select-search-container {
                                            grid-column: span 7 !important;
                                            width: 100% !important;
                                        }
                                    </style>
                                ')),
                        ])
                        ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(fn ($record, $livewire) => ($livewire->activeTab === 'terhapus' || $record->trashed()) ? 'view' : 'edit')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'parkir' => 'info',
                        'umum' => 'success',
                        'kamar_mandi' => 'warning',
                        'kamar' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'parkir' => 'Parkir',
                        'umum' => 'Umum',
                        'kamar_mandi' => 'Kamar Mandi',
                        'kamar' => 'Kamar',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('icon')
                    ->label('Icon')
                    ->icon(fn (string $state): string => $state),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('lucide-check-circle')
                    ->falseIcon('lucide-x-circle')
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
                        \Filament\Infolists\Components\Section::make('Detail Fasilitas')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Fasilitas'),
                                        TextEntry::make('slug')
                                            ->label('Slug')
                                            ->copyable(),
                                        TextEntry::make('type')
                                            ->label('Tipe')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'parkir' => 'info',
                                                'umum' => 'success',
                                                'kamar_mandi' => 'warning',
                                                'kamar' => 'primary',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'parkir' => 'Parkir',
                                                'umum' => 'Umum',
                                                'kamar_mandi' => 'Kamar Mandi',
                                                'kamar' => 'Kamar',
                                                default => $state,
                                            }),
                                        TextEntry::make('icon')
                                            ->label('Icon')
                                            ->formatStateUsing(function ($state) {
                                                $labels = IconService::getAllIcons();
                                                $label = $labels[$state] ?? $state;
                                                
                                                $svgHtml = '';
                                                if ($state) {
                                                    try {
                                                        $svgHtml = svg($state, 'w-5 h-5')->toHtml();
                                                    } catch (\Exception $e) {
                                                        $svgHtml = '<span class="text-gray-400">?</span>';
                                                    }
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex items-center gap-2">' .
                                                    $svgHtml .
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
                    ->label('Edit')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) !== 'terhapus'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? null) !== 'terhapus')
                    ->action(function (Facility $record) {
                        $record->delete();

                        Notification::make()
                            ->title('Fasilitas berhasil dihapus')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->successNotificationTitle('Fasilitas berhasil dihapus permanen')
                    ->visible(fn ($livewire) => auth()->user()->hasRole('super_admin') && ($livewire->activeTab ?? null) === 'terhapus'),
                    
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(fn ($livewire) => auth()->user()->hasRole('super_admin') && ($livewire->activeTab ?? null) === 'terhapus')
                    ->action(function (Facility $record) {
                        $targetSlug = $record->slug;
                        // Support legacy slug with __trashed__
                        if (\Illuminate\Support\Str::contains($targetSlug, '__trashed__')) {
                            $targetSlug = \Illuminate\Support\Str::before($targetSlug, '__trashed__');
                        }

                        $existsActive = Facility::query()
                            ->whereNull('deleted_at')
                            ->where('slug', $targetSlug)
                            ->where('id', '!=', $record->id)
                            ->exists();

                        if ($existsActive) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body("Tidak bisa memulihkan karena sudah ada fasilitas aktif dengan slug: {$targetSlug}.")
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($targetSlug !== $record->slug) {
                             $record->updateQuietly(['slug' => $targetSlug]);
                        }
                        
                        $record->restore();

                        Notification::make()
                            ->title('Fasilitas berhasil dipulihkan')
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
                            ->title('Fasilitas terpilih berhasil dihapus')
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
                                if (!($record instanceof Facility) || !$record->trashed()) {
                                    continue;
                                }

                                $targetSlug = $record->slug;
                                if (\Illuminate\Support\Str::contains($targetSlug, '__trashed__')) {
                                    $targetSlug = \Illuminate\Support\Str::before($targetSlug, '__trashed__');
                                }

                                $existsActive = Facility::query()
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
                                ->body("Berhasil memulihkan {$restored} fasilitas. Gagal karena slug sudah dipakai: " . implode(', ', array_unique($blockedSlugs)))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil memulihkan {$restored} fasilitas.")
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
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
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
                try {
                    $svg = svg($icon, 'w-4 h-4')->toHtml();
                } catch (\Exception $e) {
                    $svg = '';
                }

                return [$icon => '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                    $svg .
                    '<span class="sr-only">' . $label . '</span>' .
                    '</div>'
                ];
            })
            ->toArray();
    }

}