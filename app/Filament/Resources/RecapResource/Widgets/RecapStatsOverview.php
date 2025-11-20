<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RecapStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $recap = $this->record;
        $recap->load('recapType');
        
        $targetColumns = $recap->recapType->recapColumns()
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order')
            ->get();

        $rows = $recap->recapRows()->get();
        $rowCount = $rows->count();

        $stats = []; 

        // ▼▼▼ PERUBAHAN: KARTU "TOTAL DATA INPUT" PINDAH KE SINI (PERTAMA) ▼▼▼
        $stats[] = Stat::make('Total Data Input', $rowCount . ' Baris')
            ->description('Jumlah entri data')
            ->descriptionIcon('heroicon-m-list-bullet')
            ->color('primary');
        // ▲▲▲ SELESAI DIPINDAHKAN ▲▲▲

        // BARU SETELAH ITU LOOPING KARTU LAINNYA (Orderan, Harga, dll)
        foreach ($targetColumns as $column) {
            $totalValue = 0;
            $chartData = []; 
            
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                
                $foundValue = 0; 

                foreach ($flatData as $key => $val) {
                    if (str_ends_with($key, $column->name)) {
                        $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        $foundValue = (float) $cleanVal;
                        break; 
                    }
                }

                $totalValue += $foundValue;
                $chartData[] = $foundValue; 
            }

            if ($column->type === 'money') {
                $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-banknotes';
            } else {
                $formattedTotal = number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-calculator';
            }

            $label = (stripos($column->name, 'Total') === 0) 
                ? $column->name 
                : 'Total ' . $column->name;

            $stats[] = Stat::make($label, $formattedTotal)
                ->description('Akumulasi ' . $column->name)
                ->descriptionIcon($icon)
                ->color('success')
                ->chart($chartData); 
        }

        return $stats;
    }
}