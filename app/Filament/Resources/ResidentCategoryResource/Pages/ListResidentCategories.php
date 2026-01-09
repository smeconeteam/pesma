<?php

namespace App\Filament\Resources\ResidentCategoryResource\Pages;

use App\Filament\Resources\ResidentCategoryResource;
use App\Models\ResidentCategory;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListResidentCategories extends ListRecords
{
    protected static string $resource = ResidentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed())
                ->badge(fn () => ResidentCategory::query()
                    ->withoutTrashed()
                    ->count()
                ),

            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => ResidentCategory::query()
                    ->onlyTrashed()
                    ->count()
                )
                ->badgeColor('danger'),
        ];
    }

    /**
     * Reset selection saat pindah tab
     */
    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}