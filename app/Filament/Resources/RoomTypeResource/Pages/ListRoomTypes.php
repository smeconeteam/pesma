<?php

namespace App\Filament\Resources\RoomTypeResource\Pages;

use App\Filament\Resources\RoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListRoomTypes extends ListRecords
{
    protected static string $resource = RoomTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn(Builder $query) => $query),
        ];

        if (auth()->user()?->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->withoutGlobalScopes([SoftDeletingScope::class])->onlyTrashed()
                );
        }

        return $tabs;
    }
}
