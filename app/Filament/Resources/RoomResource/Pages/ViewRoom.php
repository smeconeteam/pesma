<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Models\Room;
use Filament\Actions;
use App\Models\RoomResident;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use App\Filament\Resources\RoomResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $record = $this->record;

        return [
            Actions\EditAction::make()
                ->visible(
                    fn() => ($user?->hasRole(['super_admin', 'main_admin']) ?? false)
                        && !$record->trashed()
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Lokasi')
                    ->schema([
                        TextEntry::make('block.dorm.name')->label('Cabang')->placeholder('-'),
                        TextEntry::make('block.name')->label('Komplek')->placeholder('-'),
                        TextEntry::make('roomType.name')->label('Tipe Kamar')->placeholder('-'),
                    ])
                    ->columns(3),

                InfoSection::make('Detail Kamar')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Kamar')
                            ->copyable()
                            ->copyMessage('Kode disalin'),

                        TextEntry::make('number')->label('Nomor Kamar')->placeholder('-'),

                        TextEntry::make('capacity')
                            ->label('Kapasitas')
                            ->formatStateUsing(fn($state) => $state ? "{$state} orang" : '-'),

                        TextEntry::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->money('IDR', true),
                        
                        TextEntry::make('width')
                            ->label('Lebar Kamar')
                            ->suffix(' m')
                            ->placeholder('-'),

                        TextEntry::make('length')
                            ->label('Panjang Kamar')
                            ->suffix(' m')
                            ->placeholder('-'),

                        TextEntry::make('width')
                            ->label('Lebar Kamar')
                            ->suffix(' m')
                            ->placeholder('-'),

                        TextEntry::make('length')
                            ->label('Panjang Kamar')
                            ->suffix(' m')
                            ->placeholder('-'),

                        TextEntry::make('residentCategory.name')
                            ->label('Kategori Kamar')
                            ->placeholder('-'),

                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),

                        TextEntry::make('penghuni_aktif')
                            ->label('Penghuni Aktif')
                            ->state(
                                fn(Room $record) => RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->count()
                            )
                            ->suffix(' orang'),

                        TextEntry::make('available_capacity')
                            ->label('Kapasitas Tersedia')
                            ->state(fn(Room $record) => $record->getAvailableCapacityAttribute())
                            ->suffix(' orang'),
                    ])

                    ->columns(3),

                InfoSection::make('Informasi Penanggung Jawab')
                    ->schema([
                        TextEntry::make('contactPerson.residentProfile.full_name')
                            ->label('Nama Penanggung Jawab')
                            ->icon('heroicon-m-user')
                            ->weight('semibold')
                            ->placeholder('-')
                            ->default(fn ($record) => $record->contactPerson?->name),

                        TextEntry::make('contactPerson.residentProfile.phone_number')
                            ->label('Nomor WhatsApp')
                            ->icon('heroicon-m-phone')
                            ->placeholder('-')
                            ->copyable()
                            ->copyMessage('Nomor disalin')
                            ->url(fn ($record) => $record->contactPerson?->residentProfile?->phone_number 
                                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->contactPerson->residentProfile->phone_number) 
                                : null, 
                                shouldOpenInNewTab: true)
                            ->color('success'),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => ! empty($record->contact_person_name) || ! empty($record->contact_person_number)),

                InfoSection::make('Galeri')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                \Filament\Infolists\Components\ImageEntry::make('thumbnail')
                                    ->label('Thumbnail')
                                    ->height(300)
                                    ->defaultImageUrl(url('/images/placeholder-room.jpg'))
                                    ->extraImgAttributes([
                                        'class' => 'rounded-lg shadow-md object-cover w-full',
                                        'style' => 'aspect-ratio: 16/9;'
                                    ])
                                    ->visible(fn($record) => !empty($record->thumbnail)),
                            ])
                            ->visible(fn($record) => !empty($record->thumbnail)),

                        \Filament\Infolists\Components\ImageEntry::make('images')
                            ->label('Galeri Foto')
                            ->columnSpanFull()
                            ->height(200)
                            ->extraImgAttributes([
                                'class' => 'rounded-lg shadow-sm object-cover',
                                'style' => 'aspect-ratio: 4/3;'
                            ])
                            ->visible(fn($record) => !empty($record->images) && count($record->images) > 0),

                        TextEntry::make('no_gallery')
                            ->label('')
                            ->default('Belum ada foto yang diupload')
                            ->color('gray')
                            ->visible(fn($record) => empty($record->thumbnail) && (empty($record->images) || count($record->images) === 0))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                InfoSection::make('Peraturan & Fasilitas')
                    ->schema([
                        RepeatableEntry::make('roomRules')
                            ->label('Peraturan Kamar')
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->formatStateUsing(function ($state, $record) {
                                        $icon = $record->icon ? svg($record->icon, 'w-5 h-5 text-primary-500')->toHtml() : '';
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex items-center gap-2">' .
                                                $icon .
                                                '<span>' . $state . '</span>' .
                                                '</div>'
                                        );
                                    }),
                            ])
                            ->grid(3)
                            ->visible(fn($record) => $record->roomRules()->exists())
                            ->placeholder('Belum ada peraturan kamar'),

                        Grid::make(2)
                            ->schema([
                                InfoSection::make('Parkir')
                                    ->schema([
                                        TextEntry::make('parkingFacilities_list')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $facilities = $record->parkingFacilities;
                                                if ($facilities->isEmpty()) return '';
                                                return new \Illuminate\Support\HtmlString($facilities->map(function ($facility) {
                                                    $iconHtml = '';
                                                    if ($facility->icon) {
                                                        try {
                                                            $svg = svg($facility->icon)->toHtml();
                                                            $svg = str_replace('stroke="currentColor"', 'stroke="#3b82f6"', $svg);
                                                            $base64 = base64_encode($svg);
                                                            $iconHtml = '<img src="data:image/svg+xml;base64,' . $base64 . '" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 12px;" />';
                                                        } catch (\Exception $e) { $iconHtml = ''; }
                                                    }
                                                    return '<div class="flex items-center mb-3">' . $iconHtml . '<span class="text-sm font-semibold">' . e($facility->name) . '</span></div>';
                                                })->implode(''));
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn($record) => $record->parkingFacilities()->exists())
                                    ->compact(),

                                InfoSection::make('Umum')
                                    ->schema([
                                        TextEntry::make('generalFacilities_list')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $facilities = $record->generalFacilities;
                                                if ($facilities->isEmpty()) return '';
                                                return new \Illuminate\Support\HtmlString($facilities->map(function ($facility) {
                                                    $iconHtml = '';
                                                    if ($facility->icon) {
                                                        try {
                                                            $svg = svg($facility->icon)->toHtml();
                                                            $svg = str_replace('stroke="currentColor"', 'stroke="#22c55e"', $svg);
                                                            $base64 = base64_encode($svg);
                                                            $iconHtml = '<img src="data:image/svg+xml;base64,' . $base64 . '" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 12px;" />';
                                                        } catch (\Exception $e) { $iconHtml = ''; }
                                                    }
                                                    return '<div class="flex items-center mb-3">' . $iconHtml . '<span class="text-sm font-semibold">' . e($facility->name) . '</span></div>';
                                                })->implode(''));
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn($record) => $record->generalFacilities()->exists())
                                    ->compact(),

                                InfoSection::make('Kamar Mandi')
                                    ->schema([
                                        TextEntry::make('bathroomFacilities_list')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $facilities = $record->bathroomFacilities;
                                                if ($facilities->isEmpty()) return '';
                                                return new \Illuminate\Support\HtmlString($facilities->map(function ($facility) {
                                                    $iconHtml = '';
                                                    if ($facility->icon) {
                                                        try {
                                                            $svg = svg($facility->icon)->toHtml();
                                                            $svg = str_replace('stroke="currentColor"', 'stroke="#eab308"', $svg);
                                                            $base64 = base64_encode($svg);
                                                            $iconHtml = '<img src="data:image/svg+xml;base64,' . $base64 . '" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 12px;" />';
                                                        } catch (\Exception $e) { $iconHtml = ''; }
                                                    }
                                                    return '<div class="flex items-center mb-3">' . $iconHtml . '<span class="text-sm font-semibold">' . e($facility->name) . '</span></div>';
                                                })->implode(''));
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn($record) => $record->bathroomFacilities()->exists())
                                    ->compact(),

                                InfoSection::make('Kamar')
                                    ->schema([
                                        TextEntry::make('roomFacilities_list')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $facilities = $record->roomFacilities;
                                                if ($facilities->isEmpty()) return '';
                                                return new \Illuminate\Support\HtmlString($facilities->map(function ($facility) {
                                                    $iconHtml = '';
                                                    if ($facility->icon) {
                                                        try {
                                                            $svg = svg($facility->icon)->toHtml();
                                                            // For Kamar, use primary green or white if preferred. Let's use green #22c55e
                                                            $svg = str_replace('stroke="currentColor"', 'stroke="#22c55e"', $svg);
                                                            $base64 = base64_encode($svg);
                                                            $iconHtml = '<img src="data:image/svg+xml;base64,' . $base64 . '" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 12px;" />';
                                                        } catch (\Exception $e) { $iconHtml = ''; }
                                                    }
                                                    return '<div class="flex items-center mb-3">' . $iconHtml . '<span class="text-sm font-semibold">' . e($facility->name) . '</span></div>';
                                                })->implode(''));
                                            })
                                            ->html(),
                                    ])
                                    ->visible(fn($record) => $record->roomFacilities()->exists())
                                    ->compact(),
                            ])
                            ->visible(fn($record) => $record->facilities()->exists()),
                    ])
                    ->collapsible(),

                InfoSection::make('Daftar Penghuni Aktif')
                    ->schema([
                        RepeatableEntry::make('activeRoomResidents')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('user.residentProfile.full_name')
                                            ->label('Nama Lengkap')
                                            ->placeholder('-'),

                                        TextEntry::make('user.residentProfile.student_id')
                                            ->label('NIM/NIS')
                                            ->placeholder('-'),

                                        TextEntry::make('check_in_date')
                                            ->label('Tanggal Masuk')
                                            ->date('d M Y')
                                            ->placeholder('-'),

                                        IconEntry::make('is_pic')
                                            ->label('PIC Kamar')
                                            ->boolean(),
                                    ])
                            ])
                            ->placeholder('Belum ada penghuni aktif')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn(Room $record) => $record->activeRoomResidents()->exists()),

                InfoSection::make('Riwayat Penghuni')
                    ->schema([
                        RepeatableEntry::make('roomResidents')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('user.residentProfile.full_name')
                                            ->label('Nama Lengkap')
                                            ->placeholder('-'),

                                        TextEntry::make('user.residentProfile.student_id')
                                            ->label('NIM/NIS')
                                            ->placeholder('-'),

                                        TextEntry::make('check_in_date')
                                            ->label('Tanggal Masuk')
                                            ->date('d M Y')
                                            ->placeholder('-'),

                                        TextEntry::make('check_out_date')
                                            ->label('Tanggal Keluar')
                                            ->date('d M Y')
                                            ->placeholder('Masih aktif')
                                            ->color('danger'),
                                    ])
                            ])
                            ->placeholder('Belum ada riwayat penghuni')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                InfoSection::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diubah')->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}