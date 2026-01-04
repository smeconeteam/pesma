<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\OccupancyChartWidget::class,
            \App\Filament\Widgets\GenderDistributionWidget::class,
            \App\Filament\Widgets\ResidentStatusWidget::class,
            \App\Filament\Widgets\GrowthChartWidget::class,
            \App\Filament\Widgets\DormSummaryTableWidget::class,
            \App\Filament\Widgets\LatestRegistrationsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
