<?php

namespace App\Filament\Resources\MainAdminResource\Pages;

use App\Filament\Resources\MainAdminResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMainAdmins extends ListRecords
{
    protected static string $resource = MainAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Admin Utama')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed())
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'main_admin'))
                    ->withoutTrashed()
                    ->count()
                ),

            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'main_admin'))
                    ->onlyTrashed()
                    ->count()
                )
                ->badgeColor('danger'),
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}