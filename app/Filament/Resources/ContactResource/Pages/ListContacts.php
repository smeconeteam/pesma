<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public ?string $activeTab = 'aktif';

    protected function getHeaderActions(): array
    {
        return ContactResource::canManage()
            ? [Actions\CreateAction::make()]
            : [];
    }

    public function getTabs(): array
    {
        // hanya super admin yang lihat tab terhapus
        if (! ContactResource::canSeeTrashed()) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->icon('heroicon-m-check-circle')
                ->badge(Contact::query()->withoutTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),

            'terhapus' => Tab::make('Data Terhapus')
                ->icon('heroicon-m-trash')
                ->badge(Contact::onlyTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'aktif';
    }

    /**
     * ✅ Admin cabang/komplek tidak boleh bulk, jadi checkbox tidak tampil.
     */
    protected function canSelectRecords(): bool
    {
        // checkbox hanya untuk main_admin (bulk delete) dan super_admin (bulk delete + restore)
        return ContactResource::canManage() || ContactResource::canSeeTrashed();
    }
}
