<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use App\Filament\Resources\RecapTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable; 

// Import Widget
use App\Filament\Resources\RecapResource\Widgets\RecapStatsOverview;
use App\Filament\Resources\RecapResource\Widgets\RecapTrendChart;
use App\Filament\Resources\RecapResource\Widgets\RecapDistributionChart;

class ViewRecap extends ViewRecord
{
    protected static string $resource = RecapResource::class;

    public function getTitle(): string | Htmlable
    {
        return 'Dashboard ' . $this->getRecord()->name;
    }

    // ▼▼▼ PERBAIKAN: Hapus Action Edit di Header (Return array kosong) ▼▼▼
    // Ini akan menghilangkan tombol biru "Ubah" di pojok kanan atas
    protected function getHeaderActions(): array
    {
        return []; 
    }
    // ▲▲▲ SELESAI HAPUS ▲▲▲

    protected function getHeaderWidgets(): array
    {
        return [
            RecapStatsOverview::class,      
            RecapTrendChart::class,         
            RecapDistributionChart::class,  
        ];
    }

    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();
        $record->load('recapType');

        return [
            // Arahkan breadcrumb induk ke halaman Detail Jenis Rekap
            RecapTypeResource::getUrl('view', ['record' => $record->recapType]) => $record->recapType->name,
            'view' => $record->name, 
        ];
    }
}