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

                        Forms\Components\Select::make('icon')
                            ->label('Ikon')
                            ->options(function () {
                                return collect(static::getAvailableIcons())->map(function ($label, $icon) {
                                    try {
                                        return '<div class="flex items-center gap-2">' .
                                            svg($icon, 'w-5 h-5')->toHtml() .
                                            '<span>' . $label . '</span>' .
                                            '</div>';
                                    } catch (\Exception $e) {
                                        return $label;
                                    }
                                })->toArray();
                            })
                            ->searchable()
                            ->allowHtml()
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpan(1)
                            ->getOptionLabelUsing(fn ($value) => 
                                new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-2 text-sm">' .
                                    ($value ? svg($value, 'w-5 h-5')->toHtml() : '') .
                                    '<span>' . (static::getAvailableIcons()[$value] ?? $value) . '</span>' .
                                    '</div>'
                                )
                            ),

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
                                                $labels = static::getAvailableIcons();
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
        return [
            'lucide-house' => 'Rumah',
            'lucide-building' => 'Gedung',
            'lucide-library' => 'Perpustakaan',
            'lucide-graduation-cap' => 'Akademik',
            'lucide-users' => 'Penghuni',
            'lucide-wifi' => 'WiFi',
            'lucide-tv' => 'TV',
            'lucide-monitor' => 'Komputer',
            'lucide-smartphone' => 'HP',
            'lucide-printer' => 'Printer',
            'lucide-lightbulb' => 'Lampu',
            'lucide-flame' => 'Api',
            'lucide-zap' => 'Petir',
            'lucide-sun' => 'Matahari',
            'lucide-moon' => 'Bulan',
            'lucide-sparkles' => 'Fasilitas',
            'lucide-star' => 'Bintang',
            'lucide-heart' => 'Hati',
            'lucide-shield-check' => 'Aman',
            'lucide-lock' => 'Terkunci',
            'lucide-key' => 'Kunci',
            'lucide-bell' => 'Lonceng',
            'lucide-book-open' => 'Buku',
            'lucide-calendar' => 'Kalender',
            'lucide-clock' => 'Jam',
            'lucide-flask-conical' => 'Lab',
            'lucide-wrench' => 'Alat',
            'lucide-settings' => 'Seting',
            'lucide-shopping-bag' => 'Belanja',
            'lucide-gift' => 'Hadiah',
            'lucide-map-pin' => 'Lokasi',
            'lucide-globe' => 'Dunia',
            'lucide-camera' => 'Kamera',
            'lucide-video' => 'CCTV',
            'lucide-music' => 'Musik',
            'lucide-mic' => 'Mic',
            'lucide-phone' => 'Telepon',
            'lucide-mail' => 'Email',
            'lucide-message-square' => 'Chat',
            'lucide-trash-2' => 'Sampah',
            'lucide-credit-card' => 'Kartu',
            'lucide-banknote' => 'Uang',
            'lucide-cloud' => 'Cloud',
            'lucide-search' => 'Cari',
            'lucide-layout-grid' => 'Grid',
            'lucide-box' => 'Box',
            'lucide-check-circle' => 'Ok',
            'lucide-info' => 'Info',
            'lucide-help-circle' => 'Tanya',
            'lucide-lamp' => 'Lampu Belajar',
            'lucide-fan' => 'Kipas',
            'lucide-snowflake' => 'AC',
            'lucide-container' => 'Lemari',
            'lucide-bed' => 'Kasur',
            'lucide-shirt' => 'Pakaian',
            'lucide-utensils' => 'Dapur',
            'lucide-coffee' => 'Kopi',
            'lucide-parking-circle' => 'Parkir',
            'lucide-bike' => 'Sepeda',
            'lucide-car' => 'Mobil',
            'lucide-bus' => 'Transport',
            'lucide-trees' => 'Taman',
            'lucide-shower-head' => 'Shower',
            'lucide-bath' => 'Bak Mandi',
            'lucide-waves' => 'Air',
            'lucide-droplets' => 'Cairan',
            'lucide-washing-machine' => 'Cuci',
            'lucide-plug' => 'Listrik',
            'lucide-shovel' => 'Kebun',
            'lucide-sofa' => 'Sofa',
            'lucide-table' => 'Meja',
            'lucide-armchair' => 'Kursi',
        ];
    }

    public static function getIconOptions(): array
    {
        return collect(static::getAvailableIcons())
            ->mapWithKeys(function ($label, $icon) {
                try {
                    $svg = svg($icon, 'w-4 h-4')->toHtml();
                } catch (\Exception $e) {
                    $svg = '';
                }

                return [$icon => new \Illuminate\Support\HtmlString(
                    '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                    $svg .
                    '<span class="text-[6px] font-medium text-center truncate w-full leading-none mt-0.5">' . $label . '</span>' .
                    '</div>'
                )];
            })
            ->toArray();
    }

}