<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Registration;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    public function mount(): void
    {
        // Pastikan user punya akses
        abort_unless(static::getResource()::canAccess(), 403);

        // Redirect branch_admin dan block_admin langsung ke halaman create
        $user = auth()->user();

        if ($user?->hasAnyRole(['branch_admin', 'block_admin'])) {
            redirect()->to(static::getResource()::getUrl('create'));
            return;
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pendaftaran'),

            Actions\ActionGroup::make([
                Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-text')
                    ->action(fn() => \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegistrationsExport, 'registrations.xlsx')),
                
                Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->action(fn() => \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegistrationsExport, 'registrations.pdf', \Maatwebsite\Excel\Excel::DOMPDF)),
                    
                Actions\Action::make('download_template')
                    ->label('Download Template Import')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn() => \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RegistrationTemplateExport, 'template_registrasi.xlsx')),

                Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\RegistrationsImport, $data['file']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Import Berhasil')
                            ->success()
                            ->send();
                    }),
            ])
            ->label('Menu Data')
            ->icon('heroicon-m-ellipsis-vertical')
            ->color('info')
            ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RegistrationResource\Widgets\RegistrationStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(Registration::count()),

            'menunggu' => Tab::make('Menunggu Persetujuan')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(Registration::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'disetujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved'))
                ->badge(Registration::where('status', 'approved')->count())
                ->badgeColor('success'),

            'ditolak' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(Registration::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}
