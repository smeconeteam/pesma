<?php

namespace App\Filament\Resources\FacilityResource\Pages;

use App\Filament\Resources\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFacilities extends ListRecords
{
    protected static string $resource = FacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Fasilitas'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'parkir' => Tab::make('Parkir')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'parkir'))
                ->badge(fn () => \App\Models\Facility::query()->where('type', 'parkir')->count()),

            'umum' => Tab::make('Umum')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'umum'))
                ->badge(fn () => \App\Models\Facility::query()->where('type', 'umum')->count()),

            'kamar_mandi' => Tab::make('Kamar Mandi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'kamar_mandi'))
                ->badge(fn () => \App\Models\Facility::query()->where('type', 'kamar_mandi')->count()),

            'kamar' => Tab::make('Kamar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'kamar'))
                ->badge(fn () => \App\Models\Facility::query()->where('type', 'kamar')->count()),
        ];

        // Tab data terhapus hanya untuk super_admin
        if (auth()->user()->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => \App\Models\Facility::query()->onlyTrashed()->count())
                ->badgeColor('danger');
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}