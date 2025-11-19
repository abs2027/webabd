<?php

namespace App\Filament\Resources\RecapTypeResource\Pages;

use App\Filament\Resources\RecapTypeResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;
// IMPORT DUA WIDGET
use App\Filament\Resources\RecapTypeResource\Widgets\CompareRecapsChart; 
use App\Filament\Resources\RecapTypeResource\Widgets\CompareRecapsDistributionChart; 

use Filament\Forms\Components\Select; 
use Filament\Actions\Action; 

class ViewRecapType extends ViewRecord
{
    protected static string $resource = RecapTypeResource::class;

    public function getTitle(): string
    {
        return 'Detail Jenis Rekapitulasi'; 
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter_chart')
                ->label('Pilih Periode Grafik')
                ->icon('heroicon-o-funnel') 
                ->color('info')
                ->modalWidth('lg')
                ->form([
                    Select::make('period_ids')
                        ->label('Pilih Periode (Maksimal 5)')
                        ->multiple() 
                        ->searchable() 
                        ->maxItems(5) 
                        ->minItems(2) 
                        ->options(function () {
                            return $this->getRecord()->recaps()
                                ->get() 
                                ->sortBy('name', SORT_NATURAL) 
                                ->pluck('name', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    // ▼▼▼ UPDATE DISINI: KIRIM KE DUA WIDGET SEKALIGUS ▼▼▼
                    
                    // 1. Kirim ke Grafik Trend (Line Chart)
                    $this->dispatch('update-chart-periods', periodIds: $data['period_ids'])
                         ->to(CompareRecapsChart::class);

                    // 2. Kirim ke Grafik Distribusi (Bar Chart Baru)
                    $this->dispatch('update-chart-periods', periodIds: $data['period_ids'])
                         ->to(CompareRecapsDistributionChart::class);
                         
                    // ▲▲▲ SELESAI ▲▲▲
                }),

            Actions\EditAction::make()
                ->label('Ubah Info')
                ->color('gray'),
        ];
    }

    // ▼▼▼ DAFTARKAN KEDUA WIDGET DISINI ▼▼▼
    protected function getHeaderWidgets(): array
    {
        return [
            CompareRecapsChart::class,            // Grafik 1 (Trend Progress)
            CompareRecapsDistributionChart::class, // Grafik 2 (Distribusi Kategori)
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
}