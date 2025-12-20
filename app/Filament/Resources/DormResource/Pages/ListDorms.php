<?php

namespace App\Filament\Resources\DormResource\Pages;

use App\Filament\Resources\DormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListDorms extends ListRecords
{
    protected static string $resource = DormResource::class;

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
                ->modifyQueryUsing(fn(Builder $query) => $query), // default
        ];

        if (auth()->user()?->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(function (Builder $query) {
                    // Pastikan query bisa mengakses trashed
                    $query->withoutGlobalScopes([SoftDeletingScope::class])->onlyTrashed();
                });
        }

        return $tabs;
    }
}
