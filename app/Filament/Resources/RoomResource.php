<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\Facility;
use App\Models\RoomRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $slug = 'kamar';
    protected static ?string $navigationGroup = 'Asrama';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Kamar';
    protected static ?string $modelLabel = 'Kamar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Informasi Utama')
                            ->schema([
                                Forms\Components\Section::make('Informasi Kamar')
                                    ->schema([
                        Select::make('dorm_id')
                            ->label('Cabang')
                            ->dehydrated(false)
                            ->options(function (?Room $record) {
                                $user = auth()->user();

                                $query = Dorm::query()
                                    ->whereNull('deleted_at')
                                    ->orderBy('name');

                                // Batasi pilihan berdasarkan role
                                if ($user?->hasRole('branch_admin')) {
                                    $query->whereIn('id', $user->branchDormIds());
                                } elseif ($user?->hasRole('block_admin')) {
                                    $blockDormIds = Block::query()
                                        ->whereIn('id', $user->blockIds())
                                        ->pluck('dorm_id')
                                        ->unique()
                                        ->values()
                                        ->all();

                                    $query->whereIn('id', $blockDormIds);
                                }

                                // Tampilkan hanya yang aktif, tapi tetap izinkan nilai existing (meskipun nonaktif)
                                $currentDormId = $record?->block?->dorm_id;

                                if ($currentDormId) {
                                    $query->where(function ($q) use ($currentDormId) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $currentDormId);
                                    });
                                } else {
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                if ($record?->block?->dorm_id) {
                                    $component->state($record->block->dorm_id);
                                }
                            })
                            ->afterStateUpdated(function (Set $set) {
                                $set('block_id', null);
                                $set('code', null);
                            })
                            ->disabled(function ($record) {
                                $user = auth()->user();

                                // Admin komplek tidak boleh mengubah cabang
                                if ($user?->hasRole('block_admin')) {
                                    return true;
                                }

                                if (!$record) return false;

                                return RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                            })
                            ->helperText(function ($record) {
                                $user = auth()->user();

                                if (!$record) {
                                    return 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.';
                                }

                                if ($user?->hasRole('block_admin')) {
                                    return 'Cabang dikunci untuk akun admin komplek.';
                                }

                                $hasActiveResidents = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();

                                return $hasActiveResidents
                                    ? 'Cabang tidak dapat diubah karena kamar ini masih memiliki penghuni aktif.'
                                    : 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.';
                            }),

                        Select::make('block_id')
                            ->label('Komplek')
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
                            ->options(function (Get $get, ?Room $record) {
                                $user = auth()->user();
                                $dormId = $get('dorm_id');

                                // Selain block_admin, komplek baru muncul setelah cabang dipilih
                                if (!($user?->hasRole('block_admin') ?? false) && !$dormId) {
                                    return [];
                                }

                                $query = Block::query()->orderBy('name');

                                if (!($user?->hasRole('block_admin') ?? false)) {
                                    $query->where('dorm_id', $dormId);
                                }

                                // Batasi pilihan berdasarkan role
                                if ($user?->hasRole('branch_admin')) {
                                    $allowedDormIds = $user->branchDormIds()->toArray();
                                    if ($dormId && !in_array((int) $dormId, array_map('intval', $allowedDormIds), true)) {
                                        return [];
                                    }
                                    $query->whereIn('dorm_id', $allowedDormIds);
                                } elseif ($user?->hasRole('block_admin')) {
                                    $query->whereIn('id', $user->blockIds());
                                }

                                // Tampilkan hanya yang aktif, tapi tetap izinkan nilai existing (meskipun nonaktif)
                                $currentBlockId = $record?->block_id;

                                if ($currentBlockId) {
                                    $query->where(function ($q) use ($currentBlockId) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $currentBlockId);
                                    });
                                } else {
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->disabled(function (Get $get, $record) {
                                $user = auth()->user();

                                // Create: disable sampai cabang dipilih (kecuali block_admin)
                                if (!$record) {
                                    if ($user?->hasRole('block_admin')) {
                                        return false;
                                    }
                                    return blank($get('dorm_id'));
                                }

                                // Edit: kunci jika ada penghuni aktif
                                $hasActiveResidents = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();

                                return $hasActiveResidents;
                            })
                            ->helperText(function (Get $get, $record) {
                                $user = auth()->user();

                                if (!$record) {
                                    return blank($get('dorm_id')) && !($user?->hasRole('block_admin') ?? false)
                                        ? 'Pilih cabang terlebih dahulu untuk memuat daftar komplek.'
                                        : null;
                                }

                                $hasActiveResidents = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->exists();

                                return $hasActiveResidents
                                    ? 'Komplek tidak dapat diubah karena kamar ini masih memiliki penghuni aktif.'
                                    : null;
                            }),

                        Select::make('room_type_id')
                            ->label('Tipe Kamar')
                            ->options(function (?Room $record) {
                                $query = RoomType::query()->orderBy('name');

                                if ($record && $record->exists && $record->room_type_id) {
                                    $query->where(function ($q) use ($record) {
                                        $q->where('is_active', true)
                                            ->orWhere('id', $record->room_type_id);
                                    });
                                } else {
                                    $query->where('is_active', true);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // HAPUS pemanggilan generateRoomCode dari sini

                                // Auto-fill capacity dan monthly_rate dari room type
                                if ($state) {
                                    $roomType = RoomType::find($state);
                                    if ($roomType) {
                                        $set('capacity', $roomType->default_capacity);
                                        $set('monthly_rate', $roomType->default_monthly_rate);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->maxLength(20)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => static::generateRoomCode($set, $get))
                            ->helperText('Contoh: 01, 02, 101, dst.'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kamar')
                            ->disabled()
                            ->required()
                            ->maxLength(100)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn($rule) => $rule->whereNull('deleted_at')
                            )
                            ->dehydrated(true)
                            ->readonly(),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Kapasitas')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->disabled(function (?Room $record) {
                                if (!$record) return false;
                                
                                $activeCount = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->count();
                                
                                // Disable jika ada penghuni aktif (untuk mencegah user ubah kapasitas)
                                // Tapi tetap bisa dinaikkan lewat validasi di mutate
                                return false; // Tetap enable, validasi ada di backend
                            })
                            ->helperText(function (?Room $record) {
                                if (!$record) {
                                    return 'Otomatis terisi dari tipe kamar, dapat diubah sesuai kebutuhan.';
                                }
                                
                                $activeCount = RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->count();
                                
                                if ($activeCount > 0) {
                                    return "Saat ini ada {$activeCount} penghuni aktif. Kapasitas tidak boleh kurang dari jumlah penghuni aktif.";
                                }
                                
                                return 'Otomatis terisi dari tipe kamar, dapat diubah sesuai kebutuhan.';
                            }),

                        Forms\Components\TextInput::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Rp')
                            ->helperText('Otomatis terisi dari tipe kamar, dapat diubah sesuai kebutuhan.'),
                        
                        Select::make('resident_category_id')
                            ->label('Kategori Kamar')
                            ->relationship('residentCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText(function (?Room $record) {
                                if (!$record) return null;
                                return 'Kategori hanya bisa diubah jika kamar kosong (tidak ada penghuni aktif).';
                            })
                            ->disabled(function (?Room $record) {
                                if (!$record) return false;
                                return !$record->isEmpty();
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(3)
                                    ->maxLength(500),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return \App\Models\ResidentCategory::create($data)->id;
                            }),

                        Forms\Components\TextInput::make('width')
                            ->label('Lebar Kamar')
                            ->numeric()
                            ->suffix('m')
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('length')
                            ->label('Panjang Kamar')
                            ->numeric()
                            ->suffix('m')
                            ->required()
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                                Forms\Components\Section::make('Informasi Penanggung Jawab')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_person_name')
                                            ->label('Nama Kontak (Penanggung Jawab)')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('contact_person_number')
                                            ->label('Nomor Kontak (Penanggung Jawab)')
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Foto')
                            ->schema([
                                FileUpload::make('thumbnail')
                                    ->label('Thumbnail')
                                    ->image()
                                    ->directory('room-thumbnails')
                                    ->columnSpanFull(),
                                FileUpload::make('images')
                                    ->label('Galeri Gambar (Maksimal 5)')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('room-images')
                                    ->reorderable()
                                    ->columnSpanFull()
                                    ->helperText('Maksimal 5 gambar. Jika Anda memilih lebih dari 5, sistem akan menolak file tambahan.')
                                    ->validationMessages([
                                        'max' => 'Anda hanya dapat mengunggah maksimal 5 gambar.',
                                    ]),
                            ]),

                        Tabs\Tab::make('Fasilitas & Peraturan')
                            ->schema([
                                Select::make('roomRules')
                                    ->label('Peraturan Kamar')
                                    ->relationship('roomRules', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->allowHtml()
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                                        '<div class="flex items-center gap-2">' .
                                            ($record->icon ? svg($record->icon, 'w-5 h-5')->toHtml() : '') .
                                            '<span>' . $record->name . '</span>' .
                                        '</div>'
                                    )
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Peraturan')
                                            ->required(),
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
                                                ->label('Icon')
                                                ->options(static::getIconOptions())
                                                ->allowHtml()
                                                ->searchable()
                                                ->extraAttributes(['class' => 'icon-grid-selector'])
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
                                                            $icon => new \Illuminate\Support\HtmlString(
                                                                '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                                                                svg($icon, 'w-4 h-4')->toHtml() .
                                                                '<span class="text-[6px] font-medium text-center truncate w-full leading-none mt-0.5">' . $label . '</span>' .
                                                                '</div>'
                                                            )
                                                        ])
                                                        ->toArray();
                                                })
                                                ->getOptionLabelUsing(function ($value) {
                                                    if (!$value) return null;
                                                    $label = static::getAvailableIcons()[$value] ?? $value;
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
                                                }),
                                        ])->columnSpanFull(),
                                    ]),
                                    
                                Select::make('facility_parkir')
                                    ->label('Fasilitas Parkir')
                                    ->multiple()
                                    ->allowHtml()
                                    ->searchable()
                                    ->options(fn() => Facility::where('type', 'parkir')->get()->mapWithKeys(fn ($f) => [
                                        $f->id => '<div class="flex items-center gap-2">' .
                                            ($f->icon ? svg($f->icon, 'w-5 h-5')->toHtml() : '') .
                                            '<span>' . $f->name . '</span></div>'
                                    ]))
                                    ->default(fn ($record) => $record ? $record->facilities()->where('type', 'parkir')->pluck('facilities.id')->toArray() : [])
                                    ->afterStateHydrated(function (Select $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->facilities()->where('type', 'parkir')->pluck('facilities.id')->toArray());
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required()->label('Nama Fasilitas'),
                                        Forms\Components\Hidden::make('type')->default('parkir'),
                                        Select::make('icon')
                                            ->label('Icon')
                                            ->options(static::getIconOptions())
                                            ->allowHtml()
                                            ->searchable()
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
                                            }),
                                    ])
                                    ->createOptionUsing(fn ($data) => Facility::create($data)->id),

                                Select::make('facility_umum')
                                    ->label('Fasilitas Umum')
                                    ->multiple()
                                    ->allowHtml()
                                    ->searchable()
                                    ->options(fn() => Facility::where('type', 'umum')->get()->mapWithKeys(fn ($f) => [
                                        $f->id => '<div class="flex items-center gap-2">' .
                                            ($f->icon ? svg($f->icon, 'w-5 h-5')->toHtml() : '') .
                                            '<span>' . $f->name . '</span></div>'
                                    ]))
                                    ->default(fn ($record) => $record ? $record->facilities()->where('type', 'umum')->pluck('facilities.id')->toArray() : [])
                                    ->afterStateHydrated(function (Select $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->facilities()->where('type', 'umum')->pluck('facilities.id')->toArray());
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required()->label('Nama Fasilitas'),
                                        Forms\Components\Hidden::make('type')->default('umum'),
                                        Select::make('icon')
                                            ->label('Icon')
                                            ->options(static::getIconOptions())
                                            ->allowHtml()
                                            ->searchable()
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
                                            }),
                                    ])
                                    ->createOptionUsing(fn ($data) => Facility::create($data)->id),

                                Select::make('facility_kamar_mandi')
                                    ->label('Fasilitas Kamar Mandi')
                                    ->multiple()
                                    ->allowHtml()
                                    ->searchable()
                                    ->options(fn() => Facility::where('type', 'kamar_mandi')->get()->mapWithKeys(fn ($f) => [
                                        $f->id => '<div class="flex items-center gap-2">' .
                                            ($f->icon ? svg($f->icon, 'w-5 h-5')->toHtml() : '') .
                                            '<span>' . $f->name . '</span></div>'
                                    ]))
                                    ->default(fn ($record) => $record ? $record->facilities()->where('type', 'kamar_mandi')->pluck('facilities.id')->toArray() : [])
                                    ->afterStateHydrated(function (Select $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->facilities()->where('type', 'kamar_mandi')->pluck('facilities.id')->toArray());
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required()->label('Nama Fasilitas'),
                                        Forms\Components\Hidden::make('type')->default('kamar_mandi'),
                                        Select::make('icon')
                                            ->label('Icon')
                                            ->options(static::getIconOptions())
                                            ->allowHtml()
                                            ->searchable()
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
                                            }),
                                    ])
                                    ->createOptionUsing(fn ($data) => Facility::create($data)->id),

                                Select::make('facility_kamar')
                                    ->label('Fasilitas Kamar')
                                    ->multiple()
                                    ->allowHtml()
                                    ->searchable()
                                    ->options(fn() => Facility::where('type', 'kamar')->get()->mapWithKeys(fn ($f) => [
                                        $f->id => '<div class="flex items-center gap-2">' .
                                            ($f->icon ? svg($f->icon, 'w-5 h-5')->toHtml() : '') .
                                            '<span>' . $f->name . '</span></div>'
                                    ]))
                                    ->default(fn ($record) => $record ? $record->facilities()->where('type', 'kamar')->pluck('facilities.id')->toArray() : [])
                                    ->afterStateHydrated(function (Select $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->facilities()->where('type', 'kamar')->pluck('facilities.id')->toArray());
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required()->label('Nama Fasilitas'),
                                        Forms\Components\Hidden::make('type')->default('kamar'),
                                        Select::make('icon')
                                            ->label('Icon')
                                            ->options(static::getIconOptions())
                                            ->allowHtml()
                                            ->searchable()
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
                                            }),
                                    ])
                                    ->createOptionUsing(fn ($data) => Facility::create($data)->id),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('block.dorm.name')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('block.name')
                    ->label('Komplek')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Tipe Kamar')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false)
                    ->indicateUsing(function ($state) {
                        $value = is_array($state) ? ($state['value'] ?? null) : $state;

                        if ($value === null || $value === '') return null;

                        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($bool === null) return null;

                        return [
                            Indicator::make('Status: ' . ($bool ? 'Aktif' : 'Nonaktif'))
                                ->removable(true),
                        ];
                    }),

                SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn(Builder $q, $dormId) => $q->whereHas(
                                'block',
                                fn(Builder $qb) => $qb->where('dorm_id', $dormId)
                            )
                        );
                    })
                    ->form([
                        Select::make('value')
                            ->label('Cabang')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->options(function () {
                                $user = auth()->user();
                                if (!$user) return [];

                                // ✅ tampilkan juga cabang nonaktif (yang penting tidak terhapus)
                                $query = Dorm::query()
                                    ->whereNull('deleted_at')
                                    ->orderBy('name');

                                if ($user->hasRole(['super_admin', 'main_admin'])) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('branch_admin')) {
                                    return $query->whereIn('id', $user->branchDormIds())->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockIds = $user->blockIds()->toArray();
                                    $dormIds = Block::whereIn('id', $blockIds)->pluck('dorm_id')->unique()->values()->all();
                                    return $query->whereIn('id', $dormIds)->pluck('name', 'id')->toArray();
                                }

                                return [];
                            })
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user) return null;

                                if ($user->hasRole('branch_admin')) {
                                    return $user->branchDormIds()->first();
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    return $blockId ? Block::whereKey($blockId)->value('dorm_id') : null;
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (Select $component, $state) {
                                $user = auth()->user();
                                if (!$user) return;

                                if (!blank($state)) return;

                                if ($user->hasRole('branch_admin')) {
                                    $component->state($user->branchDormIds()->first());
                                    return;
                                }

                                if ($user->hasRole('block_admin')) {
                                    $blockId = $user->blockIds()->first();
                                    if (!$blockId) return;
                                    $component->state(Block::whereKey($blockId)->value('dorm_id'));
                                }
                            })
                            ->disabled(fn() => auth()->user()?->hasRole(['branch_admin', 'block_admin']) ?? false)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('../block_id.value', null);

                                $user = auth()->user();

                                if (($user?->hasRole('branch_admin') ?? false) && blank($state)) {
                                    $set('value', $user->branchDormIds()->first());
                                }

                                if (($user?->hasRole('block_admin') ?? false) && blank($state)) {
                                    $blockId = $user->blockIds()->first();
                                    $set('value', $blockId ? Block::whereKey($blockId)->value('dorm_id') : null);
                                }
                            }),
                    ])
                    ->indicateUsing(function ($state) {
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Dorm::query()->whereKey($id)->value('name');
                        if (!$name) return null;

                        $user = auth()->user();
                        $locked = $user?->hasAnyRole(['branch_admin', 'block_admin']) ?? false;

                        return [
                            Indicator::make("Cabang: {$name}")
                                ->removable(! $locked),
                        ];
                    }),

                SelectFilter::make('block_id')
                    ->label('Komplek')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn(Builder $q, $blockId) => $q->where('block_id', $blockId)
                        );
                    })
                    ->form([
                        Select::make('value')
                            ->label('Komplek')
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->placeholder('Pilih cabang terlebih dahulu')
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user) return null;

                                if ($user->hasRole('block_admin')) {
                                    return $user->blockIds()->first();
                                }

                                return null;
                            })
                            ->afterStateHydrated(function (Select $component, $state) {
                                $user = auth()->user();
                                if (!$user) return;

                                if (!blank($state)) return;

                                if ($user->hasRole('block_admin')) {
                                    $component->state($user->blockIds()->first());
                                }
                            })
                            ->disabled(function (Get $get) {
                                $user = auth()->user();

                                if ($user?->hasRole('block_admin')) return true;

                                $dormState = $get('../dorm_id.value');
                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                return blank($dormId);
                            })
                            ->options(function (Get $get) {
                                $user = auth()->user();
                                if (!$user) return [];

                                $dormState = $get('../dorm_id.value');
                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                if (blank($dormId)) {
                                    if ($user->hasRole('block_admin')) {
                                        return Block::query()
                                            ->whereNull('deleted_at')
                                            ->whereIn('id', $user->blockIds())
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }
                                    return [];
                                }

                                $query = Block::query()
                                    ->whereNull('deleted_at')
                                    ->where('dorm_id', $dormId)
                                    ->orderBy('name');

                                if ($user->hasRole(['super_admin', 'main_admin'])) {
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('branch_admin')) {
                                    $allowedDormIds = $user->branchDormIds()->toArray();
                                    if (!in_array((int) $dormId, array_map('intval', $allowedDormIds), true)) {
                                        return [];
                                    }
                                    return $query->pluck('name', 'id')->toArray();
                                }

                                if ($user->hasRole('block_admin')) {
                                    return $query->whereIn('id', $user->blockIds())->pluck('name', 'id')->toArray();
                                }

                                return [];
                            })
                            ->helperText(function (Get $get) {
                                $dormState = $get('../dorm_id.value');
                                $dormId = is_array($dormState) ? ($dormState['value'] ?? null) : $dormState;

                                return blank($dormId)
                                    ? 'Komplek baru bisa dipilih setelah cabang dipilih.'
                                    : null;
                            })
                            ->afterStateUpdated(function (Set $set, $state) {
                                $user = auth()->user();
                                if (($user?->hasRole('block_admin') ?? false) && blank($state)) {
                                    $set('value', $user->blockIds()->first());
                                }
                            }),
                    ])
                    ->indicateUsing(function ($state) {
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->first();
                        }
                        $id = is_array($state) ? ($state['value'] ?? null) : $state;
                        if (blank($id)) return null;

                        $name = Block::query()->whereKey($id)->value('name');
                        if (!$name) return null;

                        $user = auth()->user();
                        $locked = $user?->hasRole('block_admin') ?? false;

                        return [
                            Indicator::make("Komplek: {$name}")
                                ->removable(! $locked),
                        ];
                    }),

                SelectFilter::make('room_type_id')
                    ->label('Tipe Kamar')
                    ->options(fn() => RoomType::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn(Builder $q, $typeId) => $q->where('room_type_id', $typeId)
                        );
                    })
                    ->indicateUsing(function ($state) {
                        if (blank($state)) return null;

                        $name = RoomType::query()->whereKey($state)->value('name');
                        if (!$name) return null;

                        return [
                            Indicator::make("Tipe: {$name}")->removable(true),
                        ];
                    }),

                TernaryFilter::make('is_empty')
                    ->label('Status Penghuni')
                    ->placeholder('Semua Kamar')
                    ->trueLabel('Kamar Kosong')
                    ->falseLabel('Kamar Terisi')
                    ->native(false)
                    ->queries(
                        true: fn(Builder $query) => $query->whereDoesntHave('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date')),
                        false: fn(Builder $query) => $query->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date')),
                        blank: fn(Builder $query) => $query,
                    )
                    ->indicateUsing(function ($state) {
                        $value = is_array($state) ? ($state['value'] ?? null) : $state;

                        if ($value === null || $value === '') return null;

                        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($bool === null) return null;

                        return [
                            Indicator::make('Penghuni: ' . ($bool ? 'Kamar Kosong' : 'Kamar Terisi'))
                                ->removable(true),
                        ];
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // ✅ Data terhapus tidak bisa di-edit
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn(Room $record): bool => (auth()->user()?->hasRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false)
                            && ! $record->trashed()
                    ),

                // ✅ Delete: sebelum soft delete, ubah code supaya bisa dipakai ulang
                Tables\Actions\DeleteAction::make()
                    ->visible(function (Room $record): bool {
                        $user = auth()->user();

                        if (!($user?->hasRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false)) {
                            return false;
                        }

                        if ($record->trashed()) {
                            return false;
                        }

                        return !RoomResident::query()
                            ->where('room_id', $record->id)
                            ->whereNull('check_out_date')
                            ->exists();
                    })
                    ->action(function (Room $record) {
                        DB::transaction(function () use ($record) {
                            static::releaseCodeThenSoftDelete($record);
                        });

                        Notification::make()
                            ->title('Berhasil menghapus kamar')
                            ->success()
                            ->send();
                    }),

                // ✅ Restore: ditolak jika ada kamar aktif memakai code asli
                Tables\Actions\RestoreAction::make()
                    ->visible(fn(Room $record): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $record->trashed())
                    ->action(function (Room $record) {
                        DB::transaction(function () use ($record) {
                            $originalCode = static::extractOriginalCode($record->code);

                            $existsActive = Room::query()
                                ->whereNull('deleted_at')
                                ->where('code', $originalCode)
                                ->exists();

                            if ($existsActive) {
                                Notification::make()
                                    ->title('Gagal Memulihkan')
                                    ->body("Tidak bisa memulihkan karena sudah ada kamar aktif dengan kode: {$originalCode}.")
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->updateQuietly(['code' => $originalCode]);
                            $record->restore();

                            Notification::make()
                                ->title('Berhasil memulihkan kamar')
                                ->success()
                                ->send();
                        });
                    }),

                // ✅ FORCE DELETE (hanya tab terhapus / record trashed)
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn(Room $record): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $record->trashed()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Kamar')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen kamar ini? Data yang terhapus permanen tidak dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->before(function (Tables\Actions\ForceDeleteAction $action, Room $record) {
                        // safety: jangan hapus permanen jika masih ada penghuni aktif
                        $hasActiveResidents = RoomResident::query()
                            ->where('room_id', $record->id)
                            ->whereNull('check_out_date')
                            ->exists();

                        if ($hasActiveResidents) {
                            Notification::make()
                                ->title('Tidak dapat menghapus permanen')
                                ->body('Kamar masih memiliki penghuni aktif. Checkout terlebih dahulu.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->action(function (Room $record) {
                        DB::transaction(function () use ($record) {
                            // Jika ada FK room_residents -> rooms tanpa cascade, ini mencegah error.
                            // Kalau kamu ingin riwayat tetap ada, hapus baris ini.
                            $record->roomResidents()->delete();

                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Kamar berhasil dihapus permanen.')
                            ->success()
                            ->send();
                    }),
            ])

            /**
             * ✅ Bulk action:
             * - DeleteBulkAction hanya tampil di tab aktif
             * - RestoreBulkAction hanya tampil di tab terhapus
             * - TANPA group
             */
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus')
                    ->visible(function ($livewire): bool {
                        $user = auth()->user();

                        if (! ($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
                            return false;
                        }

                        // hanya di tab aktif
                        return ($livewire->activeTab ?? null) !== 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $allowed = $records->filter(function (Room $room) {
                            return ! $room->trashed()
                                && ! RoomResident::query()
                                    ->where('room_id', $room->id)
                                    ->whereNull('check_out_date')
                                    ->exists();
                        });

                        $blocked = $records->diff($allowed);

                        if ($allowed->isEmpty()) {
                            Notification::make()
                                ->title('Aksi Dibatalkan')
                                ->body('Tidak ada kamar yang bisa dihapus. Kamar yang masih memiliki penghuni aktif tidak dapat dihapus.')
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($allowed) {
                            foreach ($allowed as $room) {
                                static::releaseCodeThenSoftDelete($room);
                            }
                        });

                        $deleted = $allowed->count();

                        if ($blocked->isNotEmpty()) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil menghapus {$deleted} kamar. Yang tidak bisa dihapus: " . $blocked->pluck('code')->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil menghapus {$deleted} kamar.")
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

                        // hanya di tab terhapus
                        return ($livewire->activeTab ?? null) === 'terhapus';
                    })
                    ->action(function (Collection $records) {
                        $restored = 0;
                        $blockedCodes = [];

                        DB::transaction(function () use ($records, &$restored, &$blockedCodes) {
                            foreach ($records as $room) {
                                if (!($room instanceof Room) || !$room->trashed()) {
                                    continue;
                                }

                                $originalCode = static::extractOriginalCode($room->code);

                                $existsActive = Room::query()
                                    ->whereNull('deleted_at')
                                    ->where('code', $originalCode)
                                    ->exists();

                                if ($existsActive) {
                                    $blockedCodes[] = $originalCode;
                                    continue;
                                }

                                $room->updateQuietly(['code' => $originalCode]);
                                $room->restore();
                                $restored++;
                            }
                        });

                        if ($restored === 0) {
                            Notification::make()
                                ->title('Gagal Memulihkan')
                                ->body('Tidak ada data yang bisa dipulihkan karena semua bentrok dengan kode kamar yang masih aktif.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!empty($blockedCodes)) {
                            Notification::make()
                                ->title('Berhasil Sebagian')
                                ->body("Berhasil memulihkan {$restored} kamar. Gagal karena kode sudah dipakai: " . collect($blockedCodes)->unique()->join(', '))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Berhasil')
                                ->body("Berhasil memulihkan {$restored} kamar.")
                                ->success()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function infolist( \Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Informasi Utama')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('code')->label('Kode Kamar'),
                        \Filament\Infolists\Components\TextEntry::make('number')->label('Nomor'),
                        \Filament\Infolists\Components\TextEntry::make('roomType.name')->label('Tipe'),
                        \Filament\Infolists\Components\TextEntry::make('residentCategory.name')->label('Kategori'),
                        \Filament\Infolists\Components\TextEntry::make('capacity')->label('Kapasitas'),
                        \Filament\Infolists\Components\TextEntry::make('monthly_rate')->label('Tarif')->money('IDR'),
                        \Filament\Infolists\Components\TextEntry::make('width')->label('Lebar')->suffix(' m'),
                        \Filament\Infolists\Components\TextEntry::make('length')->label('Panjang')->suffix(' m'),
                    ])->columns(4),

                \Filament\Infolists\Components\Section::make('Galeri')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('thumbnail')
                            ->label('Thumbnail')
                            ->extraImgAttributes(['class' => 'rounded-lg shadow-md aspect-video object-cover w-full']),
                        \Filament\Infolists\Components\ImageEntry::make('images')
                            ->label('Galeri')
                            ->simpleLightbox()
                            ->columnSpanFull(),
                    ]),

                \Filament\Infolists\Components\Section::make('Fasilitas & Aturan')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('roomRules')
                            ->label('Peraturan Kamar')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('name')
                                    ->formatStateUsing(function ($state, $record) {
                                        $icon = $record->icon ? svg($record->icon, 'w-5 h-5 text-primary-500 inline mr-2')->toHtml() : '';
                                        return new \Illuminate\Support\HtmlString($icon . $state);
                                    }),
                            ])->grid(3),

                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\Section::make('Parkir')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('facilitiesParkir_list')
                                            ->hiddenLabel()
                                            ->state(fn ($record) => $record->facilitiesParkir)
                                            ->formatStateUsing(function ($state) {
                                                return $state->map(function ($facility) {
                                                     $icon = $facility->icon ? svg($facility->icon, 'w-5 h-5 text-info-500 inline mr-2')->toHtml() : '';
                                                     return '<div class="flex items-center mb-1">' . $icon . '<span>' . e($facility->name) . '</span></div>';
                                                })->implode('');
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn ($record) => $record->facilitiesParkir()->exists())
                                    ->compact(),

                                \Filament\Infolists\Components\Section::make('Umum')
                                    ->icon('heroicon-o-building-storefront')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('facilitiesUmum_list')
                                            ->hiddenLabel()
                                            ->state(fn ($record) => $record->facilitiesUmum)
                                            ->formatStateUsing(function ($state) {
                                                return $state->map(function ($facility) {
                                                     $icon = $facility->icon ? svg($facility->icon, 'w-5 h-5 text-success-500 inline mr-2')->toHtml() : '';
                                                     return '<div class="flex items-center mb-1">' . $icon . '<span>' . e($facility->name) . '</span></div>';
                                                })->implode('');
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn ($record) => $record->facilitiesUmum()->exists())
                                    ->compact(),

                                \Filament\Infolists\Components\Section::make('Kamar Mandi')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('facilitiesKamarMandi_list')
                                            ->hiddenLabel()
                                            ->state(fn ($record) => $record->facilitiesKamarMandi)
                                            ->formatStateUsing(function ($state) {
                                                return $state->map(function ($facility) {
                                                     $icon = $facility->icon ? svg($facility->icon, 'w-5 h-5 text-warning-500 inline mr-2')->toHtml() : '';
                                                     return '<div class="flex items-center mb-1">' . $icon . '<span>' . e($facility->name) . '</span></div>';
                                                })->implode('');
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn ($record) => $record->facilitiesKamarMandi()->exists())
                                    ->compact(),

                                \Filament\Infolists\Components\Section::make('Kamar')
                                    ->icon('heroicon-o-home')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('facilitiesKamar_list')
                                            ->hiddenLabel()
                                            ->state(fn ($record) => $record->facilitiesKamar)
                                            ->formatStateUsing(function ($state) {
                                                return $state->map(function ($facility) {
                                                     $icon = $facility->icon ? svg($facility->icon, 'w-5 h-5 text-primary-500 inline mr-2')->toHtml() : '';
                                                     return '<div class="flex items-center mb-1">' . $icon . '<span>' . e($facility->name) . '</span></div>';
                                                })->implode('');
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn ($record) => $record->facilitiesKamar()->exists())
                                    ->compact(),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereHas('block.dorm')
            ->with([
                'block.dorm',
                'roomType',
                'facilities',
                'roomRules',
                'residentCategory',
                'activeRoomResidents.user.residentProfile',
                'roomResidents.user.residentProfile'
            ]);

        // Hanya super_admin yang bisa lihat data terhapus
        if ($user?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        if ($user->hasRole('branch_admin')) {
            return $query->whereHas(
                'block',
                fn(Builder $q) => $q->whereIn('dorm_id', $user->branchDormIds())
            );
        }

        if ($user->hasRole('block_admin')) {
            return $query->whereIn('block_id', $user->blockIds());
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole([
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
        return auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false;
    }

    // ✅ Data terhapus tidak bisa di-edit
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!($user?->hasRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']) ?? false)) {
            return false;
        }

        if ($record && method_exists($record, 'trashed') && $record->trashed()) {
            return false;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole([
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
        ]) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/buat'),
            'edit'   => Pages\EditRoom::route('/{record}/edit'),
            'view'   => Pages\ViewRoom::route('/{record}'),
        ];
    }

    protected static function generateRoomCode(Set $set, Get $get): void
    {
        $dormId  = $get('dorm_id');
        $blockId = $get('block_id');
        $number  = $get('number');

        if (!$dormId || !$blockId || !$number) {
            $set('code', null);
            return;
        }

        $dorm  = Dorm::find($dormId);
        $block = Block::find($blockId);

        if (!$dorm || !$block) {
            $set('code', null);
            return;
        }

        // Format: cabang-komplek-nomor
        // Contoh: coba-komplek-01
        $code = Str::slug($dorm->name) . '-' . Str::slug($block->name) . '-' . $number;

        $set('code', strtolower($code));
    }

    protected static function releaseCodeThenSoftDelete(Room $room): void
    {
        if ($room->trashed()) {
            return;
        }

        $original = $room->code;

        if (!Str::contains($original, '__trashed__')) {
            $suffix = '__trashed__' . $room->id . '__' . now()->timestamp;
            $room->updateQuietly([
                'code' => $original . $suffix,
            ]);
        }

        $room->delete();
    }

    protected static function extractOriginalCode(string $code): string
    {
        return Str::before($code, '__trashed__');
    }

    public static function getAvailableIcons(): array
    {
        return [
            'lucide-home' => 'Kamar',
            'lucide-bed' => 'Tempat Tidur',
            'lucide-armchair' => 'Kursi',
            'lucide-table' => 'Meja',
            'lucide-lamp' => 'Lampu',
            'lucide-fan' => 'Kipas',
            'lucide-snowflake' => 'AC',
            'lucide-wifi' => 'WiFi',
            'lucide-tv' => 'TV',
            'lucide-utensils' => 'Dapur',
            'lucide-shower-head' => 'Shower',
            'lucide-bath' => 'Bak Mandi',
            'lucide-parking-circle' => 'Parkir',
            'lucide-bike' => 'Sepeda',
            'lucide-car' => 'Mobil',
            'lucide-trees' => 'Taman',
            'lucide-shield-check' => 'Aman',
            'lucide-lock' => 'Kunci',
            'lucide-bell' => 'Lonceng',
            'lucide-book-open' => 'Buku',
            'lucide-plug' => 'Listrik',
            'lucide-washing-machine' => 'Cuci',
            'lucide-trash-2' => 'Sampah',
            'lucide-camera' => 'Kamera',
            'lucide-video' => 'CCTV',
        ];
    }

    public static function getIconOptions(): array
    {
        return collect(static::getAvailableIcons())
            ->mapWithKeys(function ($label, $icon) {
                $svgHtml = '';
                try {
                    $svgHtml = svg($icon, 'w-4 h-4')->toHtml();
                } catch (\Exception $e) {
                    $svgHtml = '';
                }

                return [$icon => new \Illuminate\Support\HtmlString(
                    '<div class="flex flex-col items-center justify-center p-0.5 border rounded hover:bg-primary-50 dark:hover:bg-primary-950 transition-all border-dashed dark:border-gray-700 w-full aspect-square" title="' . $label . '">' .
                    $svgHtml .
                    '<span class="text-[6px] font-medium text-center truncate w-full leading-none mt-0.5">' . $label . '</span>' .
                    '</div>'
                )];
            })
            ->toArray();
    }
}
