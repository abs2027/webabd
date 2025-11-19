<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RecapStatsOverview extends BaseWidget
{
    public ?Model $record = null;
    

    // ▼▼▼ 2. PAKSA FULL WIDTH (Agar memanjang di atas) ▼▼▼
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }
        
        // ... (Kode logika getStats biarkan sama seperti sebelumnya) ...
        // Saya hanya menyalin bagian atas untuk konteks
        
        $recap = $this->record;
        $recap->load('recapType');
        $recapType = $recap->recapType;

        $targetColumn = $recapType->recapColumns()
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order')
            ->first();

        $rows = $recap->recapRows()->get();
        $totalValue = 0;
        $rowCount = $rows->count();
        
        if ($targetColumn) {
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                foreach ($flatData as $key => $val) {
                    if (str_ends_with($key, $targetColumn->name)) {
                        $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        $totalValue += (float) $cleanVal;
                        break;
                    }
                }
            }
        }

        $formattedTotal = $totalValue;
        if ($targetColumn && $targetColumn->type === 'money') {
            $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
        } else {
            $formattedTotal = number_format($totalValue, 0, ',', '.');
        }

        $averageValue = $rowCount > 0 ? ($totalValue / $rowCount) : 0;
        $formattedAvg = $targetColumn && $targetColumn->type === 'money' 
            ? 'Rp ' . number_format($averageValue, 0, ',', '.') 
            : number_format($averageValue, 2, ',', '.');

        return [
            Stat::make('Total ' . ($targetColumn->name ?? 'Nilai'), $formattedTotal)
                ->description('Akumulasi seluruh periode')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Rata-rata per Data', $formattedAvg)
                ->description('Nilai rata-rata harian/transaksi')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Total Data', $rowCount . ' Baris')
                ->description('Jumlah entri yang diinput')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('primary'),
        ];
    }
}