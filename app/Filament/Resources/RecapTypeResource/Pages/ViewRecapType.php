<?php

namespace App\Filament\Resources\RecapTypeResource\Pages;

use App\Filament\Resources\RecapTypeResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\RecapTypeResource\Widgets\CompareRecapsChart; 
use App\Filament\Resources\RecapTypeResource\Widgets\CompareRecapsDistributionChart; 

use Filament\Forms\Components\Select; 
use Filament\Actions\Action; 

class ViewRecapType extends ViewRecord
{
    protected static string $resource = RecapTypeResource::class;

    // ▼▼▼ 1. TAMBAHKAN PROPERTI UNTUK MENYIMPAN MEMORI FILTER ▼▼▼
    public array $savedPeriodIds = [];
    public string $savedChartMode = 'cumulative'; // Default awal
    // ▲▲▲ SELESAI ▲▲▲

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
                
                // ▼▼▼ 2. ISI FORM DENGAN DATA YANG TERSIMPAN SEBELUMNYA ▼▼▼
                ->fillForm(fn (): array => [
                    'period_ids' => $this->savedPeriodIds,
                    'chart_mode' => $this->savedChartMode,
                ])
                // ▲▲▲ SELESAI ▲▲▲

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

                    Select::make('chart_mode')
                        ->label('Mode Tampilan Grafik')
                        ->options([
                            'cumulative' => 'Progress Akumulasi (Kurva Naik)',
                            'daily' => 'Fluktuasi Harian (Naik Turun)',
                        ])
                        ->default('cumulative')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // ▼▼▼ 3. SIMPAN PILIHAN USER KE MEMORI (PROPERTI) ▼▼▼
                    $this->savedPeriodIds = $data['period_ids'];
                    $this->savedChartMode = $data['chart_mode'];
                    // ▲▲▲ SELESAI ▲▲▲

                    // Kirim data ke Widget
                    $this->dispatch('update-chart-periods', 
                        periodIds: $data['period_ids'], 
                        mode: $data['chart_mode'] 
                    )->to(CompareRecapsChart::class);

                    $this->dispatch('update-chart-periods', 
                        periodIds: $data['period_ids']
                    )->to(CompareRecapsDistributionChart::class);
                }),

            Actions\EditAction::make()
                ->label('Ubah Info')
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CompareRecapsChart::class,            
            CompareRecapsDistributionChart::class, 
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
}