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
                            ->options(static::getIconOptions())
                            ->searchable()
                            ->native(false)
                            ->allowHtml()
                            ->getSearchResultsUsing(function (string $search) {
                                if (empty($search)) {
                                    return static::getIconOptions();
                                }
                                
                                $icons = static::getAvailableIcons();
                                
                                return collect($icons)
                                    ->filter(fn ($label, $icon) => 
                                        stripos($label, $search) !== false || 
                                        stripos($icon, $search) !== false
                                    )
                                    ->mapWithKeys(fn ($label, $icon) => [
                                        $icon => '<div class="flex items-center gap-2">' .
                                            svg($icon, 'w-5 h-5')->toHtml() .
                                            '<span>' . $label . '</span>' .
                                            '</div>'
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                if (!$value) return null;
                                $label = static::getAvailableIcons()[$value] ?? $value;
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-2">' .
                                    svg($value, 'w-5 h-5')->toHtml() .
                                    '<span>' . $label . '</span>' .
                                    '</div>'
                                );
                            })
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
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
            'heroicon-o-home' => 'Rumah',
            'heroicon-o-building-office' => 'Gedung Kantor',
            'heroicon-o-building-library' => 'Perpustakaan',
            'heroicon-o-academic-cap' => 'Topi Akademik',
            'heroicon-o-users' => 'Pengguna',
            'heroicon-o-user-group' => 'Grup Pengguna',
            'heroicon-o-wifi' => 'WiFi',
            'heroicon-o-tv' => 'TV',
            'heroicon-o-computer-desktop' => 'Komputer Desktop',
            'heroicon-o-device-phone-mobile' => 'HP',
            'heroicon-o-device-tablet' => 'Tablet',
            'heroicon-o-printer' => 'Printer',
            'heroicon-o-light-bulb' => 'Lampu',
            'heroicon-o-fire' => 'Api',
            'heroicon-o-bolt' => 'Petir',
            'heroicon-o-sun' => 'Matahari',
            'heroicon-o-moon' => 'Bulan',
            'heroicon-o-sparkles' => 'Kilauan',
            'heroicon-o-star' => 'Bintang',
            'heroicon-o-heart' => 'Hati',
            'heroicon-o-shield-check' => 'Perisai Centang',
            'heroicon-o-lock-closed' => 'Kunci Tertutup',
            'heroicon-o-lock-open' => 'Kunci Terbuka',
            'heroicon-o-key' => 'Kunci',
            'heroicon-o-bell' => 'Lonceng',
            'heroicon-o-book-open' => 'Buku Terbuka',
            'heroicon-o-newspaper' => 'Koran',
            'heroicon-o-document' => 'Dokumen',
            'heroicon-o-folder' => 'Folder',
            'heroicon-o-clipboard' => 'Clipboard',
            'heroicon-o-calendar' => 'Kalender',
            'heroicon-o-clock' => 'Jam',
            'heroicon-o-beaker' => 'Gelas Kimia',
            'heroicon-o-wrench-screwdriver' => 'Kunci dan Obeng',
            'heroicon-o-cog-6-tooth' => 'Pengaturan',
            'heroicon-o-shopping-bag' => 'Tas Belanja',
            'heroicon-o-shopping-cart' => 'Keranjang Belanja',
            'heroicon-o-gift' => 'Hadiah',
            'heroicon-o-truck' => 'Truk',
            'heroicon-o-map' => 'Peta',
            'heroicon-o-map-pin' => 'Pin Peta',
            'heroicon-o-globe-alt' => 'Bola Dunia',
            'heroicon-o-flag' => 'Bendera',
            'heroicon-o-camera' => 'Kamera',
            'heroicon-o-video-camera' => 'Video Kamera',
            'heroicon-o-musical-note' => 'Not Musik',
            'heroicon-o-microphone' => 'Mikrofon',
            'heroicon-o-phone' => 'Telepon',
            'heroicon-o-envelope' => 'Amplop',
            'heroicon-o-chat-bubble-left-right' => 'Chat',
            'heroicon-o-inbox' => 'Inbox',
            'heroicon-o-archive-box' => 'Kotak Arsip',
            'heroicon-o-trash' => 'Tempat Sampah',
            'heroicon-o-credit-card' => 'Kartu Kredit',
            'heroicon-o-banknotes' => 'Uang Kertas',
            'heroicon-o-cloud' => 'Awan',
            'heroicon-o-arrow-path' => 'Panah Melingkar',
            'heroicon-o-arrow-up-tray' => 'Unggah',
            'heroicon-o-arrow-down-tray' => 'Unduh',
            'heroicon-o-magnifying-glass' => 'Kaca Pembesar',
            'heroicon-o-funnel' => 'Filter',
            'heroicon-o-bars-3' => 'Menu',
            'heroicon-o-squares-2x2' => 'Kotak',
            'heroicon-o-squares-plus' => 'Tambah Kotak',
            'heroicon-o-square-3-stack-3d' => 'Tumpukan 3D',
            'heroicon-o-cube' => 'Kubus',
            'heroicon-o-rectangle-stack' => 'Tumpukan',
            'heroicon-o-window' => 'Jendela',
            'heroicon-o-check' => 'Centang',
            'heroicon-o-check-circle' => 'Centang Lingkaran',
            'heroicon-o-x-mark' => 'Silang',
            'heroicon-o-x-circle' => 'Silang Lingkaran',
            'heroicon-o-exclamation-circle' => 'Seru Lingkaran',
            'heroicon-o-exclamation-triangle' => 'Seru Segitiga',
            'heroicon-o-information-circle' => 'Info Lingkaran',
            'heroicon-o-question-mark-circle' => 'Tanya Lingkaran',
            'heroicon-o-plus' => 'Plus',
            'heroicon-o-minus' => 'Minus',
            'heroicon-o-ellipsis-horizontal' => 'Titik Tiga',
        ];
    }

    public static function getIconOptions(): array
    {
        return collect(static::getAvailableIcons())
            ->mapWithKeys(function ($label, $icon) {
                return [$icon => '<div class="flex items-center gap-2">' .
                        svg($icon, 'w-5 h-5')->toHtml() .
                        '<span>' . $label . '</span>' .
                        '</div>'];
            })
            ->toArray();
    }

}