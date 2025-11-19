<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RecapStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    // Agar widget memanjang penuh
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $recap = $this->record;
        $recap->load('recapType');
        
        // 1. AMBIL SEMUA KOLOM TARGET
        $targetColumns = $recap->recapType->recapColumns()
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order')
            ->get();

        // Ambil data baris
        $rows = $recap->recapRows()->get();
        $rowCount = $rows->count();

        $stats = []; 

        // 2. LOOPING SETIAP KOLOM TARGET
        foreach ($targetColumns as $column) {
            $totalValue = 0;
            $chartData = []; // Array untuk menampung data grafik kecil (sparkline)
            
            // Hitung total & kumpulkan data untuk chart
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                
                $foundValue = 0; // Default 0 jika data baris ini kosong

                foreach ($flatData as $key => $val) {
                    if (str_ends_with($key, $column->name)) {
                        $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        $foundValue = (float) $cleanVal;
                        break; 
                    }
                }

                $totalValue += $foundValue;
                $chartData[] = $foundValue; // Masukkan ke array chart
            }

            // Format Tampilan (Uang vs Angka)
            if ($column->type === 'money') {
                $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-banknotes';
            } else {
                $formattedTotal = number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-calculator';
            }

            // ▼▼▼ LOGIKA PERBAIKAN LABEL ▼▼▼
            // Cek apakah nama kolom sudah diawali kata "Total" (case-insensitive)
            // Jika nama kolom: "Total Harga" -> Label: "Total Harga"
            // Jika nama kolom: "Orderan"     -> Label: "Total Orderan"
            $label = (stripos($column->name, 'Total') === 0) 
                ? $column->name 
                : 'Total ' . $column->name;

            // 3. BUAT KARTU
            $stats[] = Stat::make($label, $formattedTotal)
                ->description('Akumulasi ' . $column->name)
                ->descriptionIcon($icon)
                ->color('success')
                ->chart($chartData); // Gunakan data asli untuk grafik background
        }

        // 4. KARTU TOTAL BARIS
        $stats[] = Stat::make('Total Data Input', $rowCount . ' Baris')
            ->description('Jumlah entri data')
            ->descriptionIcon('heroicon-m-list-bullet')
            ->color('primary');

        return $stats;
    }
}