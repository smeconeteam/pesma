<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Filament\Exports\ResidentExport;
use App\Filament\Imports\ResidentImport;
use App\Exports\ResidentTemplateExport;
use App\Services\ResidentPdfExport;
use App\Models\User;
use App\Models\Dorm;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class ListResidents extends ListRecords
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ACTION: Download Template
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    return Excel::download(
                        new ResidentTemplateExport(),
                        'template-import-penghuni-' . now()->format('Y-m-d') . '.xlsx'
                    );
                })
                ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'main_admin'])),

            // ACTION: Import Excel
            Actions\ImportAction::make()
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->importer(ResidentImport::class)
                ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'main_admin']))
                ->modalHeading('Import Data Penghuni')
                ->modalDescription('Upload file Excel untuk mengimport data penghuni baru')
                ->modalSubmitActionLabel('Import')
                ->successNotificationTitle('Import Berhasil')
                ->failureNotificationTitle('Import Gagal')
                ->chunkSize(100),

            // ACTION: Export Excel - DENGAN QUEUE
            Actions\ExportAction::make()
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->exporter(ResidentExport::class)
                ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']))
                ->modalHeading('Export Data Penghuni ke Excel')
                ->modalDescription('Export akan diproses di background. Anda akan menerima notifikasi ketika selesai.')
                ->modalSubmitActionLabel('Export')
                ->fileName(fn() => 'data-penghuni-' . now()->format('Y-m-d-His'))
                ->modifyQueryUsing(function (Builder $query) {
                    // Apply tab filter
                    $activeTab = $this->activeTab ?? 'aktif';
                    
                    if ($activeTab === 'aktif') {
                        $query->withoutTrashed()
                            ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'));
                    } elseif ($activeTab === 'keluar') {
                        $query->withoutTrashed()
                            ->whereHas('roomResidents', function (Builder $q) {
                                $q->whereNotNull('check_out_date');
                            })
                            ->whereDoesntHave('roomResidents', function (Builder $q) {
                                $q->whereNull('check_out_date');
                            });
                    } elseif ($activeTab === 'terhapus') {
                        $query->onlyTrashed();
                    }

                    // Apply table filters
                    $tableFilters = $this->tableFilters;
                    
                    if (!empty($tableFilters)) {
                        // Gender filter
                        if (isset($tableFilters['gender']['value']) && !empty($tableFilters['gender']['value'])) {
                            $query->whereHas('residentProfile', function (Builder $q) use ($tableFilters) {
                                $q->where('gender', $tableFilters['gender']['value']);
                            });
                        }

                        // Dorm filter
                        if (isset($tableFilters['dorm_id']['value']) && !empty($tableFilters['dorm_id']['value'])) {
                            $dormId = $tableFilters['dorm_id']['value'];
                            $query->whereHas('roomResidents', function (Builder $q) use ($dormId) {
                                $q->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        }

                        // Block filter
                        if (isset($tableFilters['block_id']['value']) && !empty($tableFilters['block_id']['value'])) {
                            $blockId = $tableFilters['block_id']['value'];
                            $query->whereHas('roomResidents', function (Builder $q) use ($blockId) {
                                $q->whereHas('room', fn(Builder $room) => $room->where('block_id', $blockId));
                            });
                        }
                    }

                    // Apply search
                    if ($search = $this->tableSearch) {
                        $query->where(function (Builder $q) use ($search) {
                            $q->where('email', 'like', "%{$search}%")
                                ->orWhereHas('residentProfile', function (Builder $profile) use ($search) {
                                    $profile->where('full_name', 'like', "%{$search}%")
                                        ->orWhere('phone_number', 'like', "%{$search}%")
                                        ->orWhere('national_id', 'like', "%{$search}%")
                                        ->orWhere('student_id', 'like', "%{$search}%");
                                });
                        });
                    }
                    
                    return $query;
                })
                ->chunkSize(500),

            // ACTION: Export PDF
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin']))
                ->requiresConfirmation()
                ->modalHeading('Export Data Penghuni ke PDF')
                ->modalDescription('Export data penghuni yang sedang ditampilkan ke file PDF (landscape). Maksimal 1000 data.')
                ->modalSubmitActionLabel('Export PDF')
                ->action(function () {
                    try {
                        // Tingkatkan limit untuk proses PDF
                        set_time_limit(300); // 5 menit
                        ini_set('memory_limit', '512M');
                        
                        $query = $this->getCustomFilteredQuery();
                        
                        // Batasi jumlah data untuk PDF
                        $count = $query->count();
                        if ($count > 1000) {
                            Notification::make()
                                ->warning()
                                ->title('Terlalu Banyak Data')
                                ->body('PDF export dibatasi maksimal 1000 data. Saat ini ada ' . number_format($count) . ' data. Gunakan filter atau export Excel untuk data lebih banyak.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        if ($count === 0) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak Ada Data')
                                ->body('Tidak ada data penghuni untuk di-export')
                                ->send();
                            return;
                        }

                        // Load relationships yang diperlukan
                        $residents = $query->with([
                            'residentProfile.residentCategory',
                            'residentProfile.country',
                        ])->get();

                        // Ambil info filter yang aktif
                        $filters = $this->getActiveFiltersInfo();

                        $pdfExporter = new ResidentPdfExport();
                        return $pdfExporter->export($residents, $filters);

                    } catch (\Exception $e) {
                        \Log::error('PDF Export Error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        Notification::make()
                            ->danger()
                            ->title('Export Gagal')
                            ->body('Terjadi kesalahan saat export PDF. Error: ' . $e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    protected function getCustomFilteredQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        // Apply tab filter
        $activeTab = $this->activeTab ?? 'aktif';

        if ($activeTab === 'aktif') {
            $query->withoutTrashed()
                ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'));
        } elseif ($activeTab === 'keluar') {
            $query->withoutTrashed()
                ->whereHas('roomResidents', function (Builder $q) {
                    $q->whereNotNull('check_out_date');
                })
                ->whereDoesntHave('roomResidents', function (Builder $q) {
                    $q->whereNull('check_out_date');
                });
        } elseif ($activeTab === 'terhapus') {
            $query->onlyTrashed();
        }

        // Apply table filters
        $tableFilters = $this->tableFilters;

        if (!empty($tableFilters)) {
            // Gender filter
            if (isset($tableFilters['gender']['value']) && !empty($tableFilters['gender']['value'])) {
                $query->whereHas('residentProfile', function (Builder $q) use ($tableFilters) {
                    $q->where('gender', $tableFilters['gender']['value']);
                });
            }

            // Dorm filter
            if (isset($tableFilters['dorm_id']['value']) && !empty($tableFilters['dorm_id']['value'])) {
                $dormId = $tableFilters['dorm_id']['value'];
                $query->whereHas('roomResidents', function (Builder $q) use ($dormId) {
                    $q->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                });
            }

            // Block filter
            if (isset($tableFilters['block_id']['value']) && !empty($tableFilters['block_id']['value'])) {
                $blockId = $tableFilters['block_id']['value'];
                $query->whereHas('roomResidents', function (Builder $q) use ($blockId) {
                    $q->whereHas('room', fn(Builder $room) => $room->where('block_id', $blockId));
                });
            }
        }

        // Apply search
        if ($search = $this->tableSearch) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('residentProfile', function (Builder $profile) use ($search) {
                        $profile->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('national_id', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    protected function getActiveFiltersInfo(): array
    {
        $filters = [];
        $tableFilters = $this->tableFilters;

        if (!empty($tableFilters)) {
            // Gender
            if (isset($tableFilters['gender']['value']) && !empty($tableFilters['gender']['value'])) {
                $filters['gender'] = $tableFilters['gender']['value'];
            }

            // Dorm
            if (isset($tableFilters['dorm_id']['value']) && !empty($tableFilters['dorm_id']['value'])) {
                $dorm = Dorm::find($tableFilters['dorm_id']['value']);
                if ($dorm) {
                    $filters['dorm'] = $dorm->name;
                }
            }

            // Block
            if (isset($tableFilters['block_id']['value']) && !empty($tableFilters['block_id']['value'])) {
                $block = Block::find($tableFilters['block_id']['value']);
                if ($block) {
                    $filters['block'] = $block->name;
                }
            }
        }

        return $filters;
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        // Tabs untuk semua admin kecuali super_admin (tidak ada tab Terhapus)
        $tabs = [
            'aktif' => Tab::make('Aktif')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'));
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'))
                        ->withoutTrashed();

                    // Filter berdasarkan role
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                            $q->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                        });
                    } elseif ($user->hasRole('block_admin')) {
                        $blockIds = $user->blockIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                            $q->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                        });
                    }

                    return $query->count();
                })
                ->badgeColor('success'),

            'keluar' => Tab::make('Keluar')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        ->whereHas('roomResidents', function (Builder $q) {
                            // Pernah punya kamar
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            // Tidak punya kamar aktif saat ini
                            $q->whereNull('check_out_date');
                        });
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->withoutTrashed()
                        ->whereHas('roomResidents', function (Builder $q) {
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            $q->whereNull('check_out_date');
                        });

                    // Filter berdasarkan role
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                            $q->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                        });
                    } elseif ($user->hasRole('block_admin')) {
                        $blockIds = $user->blockIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                            $q->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                        });
                    }

                    return $query->count();
                })
                ->badgeColor('warning'),
        ];

        // Tab Terhapus hanya untuk super_admin
        if ($user->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Terhapus')
                ->modifyQueryUsing(fn(Builder $query) => $query->onlyTrashed())
                ->badge(
                    fn() => User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->onlyTrashed()
                        ->count()
                )
                ->badgeColor('danger');
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidentResource\Widgets\ResidentStatsOverview::class,
        ];
    }
}