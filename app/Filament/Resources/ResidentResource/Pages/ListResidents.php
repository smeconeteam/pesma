<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListResidents extends ListRecords
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'aktif' => Tab::make('Data Aktif'),
        ];

        if (auth()->user()?->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn(Builder $q) => $q->onlyTrashed());
        }

        return $tabs;
    }
}
