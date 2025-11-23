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
use App\Filament\Resources\RecapResource\Widgets\RecapCostByLocationChart;

class ViewRecap extends ViewRecord
{
    protected static string $resource = RecapResource::class;

    public function getTitle(): string | Htmlable
    {
        return 'Dashboard  - ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        // Cek status Privacy Mode dari session
        $isHidden = session()->get('privacy_mode', false);

        return [
            // Tombol Privacy Mode (Mata)
            Actions\Action::make('toggle_privacy')
                ->label($isHidden ? 'Tampilkan Angka' : 'Sensor Angka')
                ->icon($isHidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                ->color('gray')
                ->action(function () use ($isHidden) {
                    session()->put('privacy_mode', !$isHidden);
                    return redirect(request()->header('Referer'));
                }),
        ]; 
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // 1. Statistik Utama (Kartu Angka)
            RecapStatsOverview::class,      
            
            // 2. Grafik Tren (Garis) - Paling cocok lebar penuh
            RecapTrendChart::class,   

            // 3. Grafik Distribusi (Batang) & Biaya (Donut) - Berdampingan
            RecapDistributionChart::class,
            RecapCostByLocationChart::class, 
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