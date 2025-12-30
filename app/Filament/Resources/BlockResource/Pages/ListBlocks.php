<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBlocks extends ListRecords
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed())
                ->badge(fn () => Block::query()
                    ->whereHas('dorm')
                    ->withoutTrashed()
                    ->count()
                ),

            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => Block::query()
                    ->whereHas('dorm')
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
