<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use App\Filament\Resources\RecapResource\Widgets\RecapDistributionChart;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\RecapResource\Widgets\RecapTrendChart; 
use App\Filament\Resources\RecapResource\Widgets\RecapStatsOverview; // Import Widget Baru

class ViewRecap extends ViewRecord
{
    protected static string $resource = RecapResource::class;

    public function getTitle(): string
    {
        return 'Input Data Rekapitulasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ubah Info Periode')
                ->color('gray'),
        ];
    }

    // ▼▼▼ UPDATE BAGIAN INI ▼▼▼
    protected function getHeaderWidgets(): array
    {
        return [
            RecapStatsOverview::class,     // baris 1 full
            RecapTrendChart::class,        // baris 2 kolom 1
            RecapDistributionChart::class, // baris 2 kolom 2
        ];
    }


    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'lg' => 2, // 2 kolom di layar besar
        ];
    }
}