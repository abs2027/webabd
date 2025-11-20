<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RecapTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Data';
    protected static ?string $maxHeight = '150px';
    protected int | string | array $columnSpan = 'full';
    
    public ?Model $record = null;
    public ?string $filter = null; 

    // 1. ISI FILTER DENGAN KOLOM ROLE 'METRIC_SUM'
    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        // Hanya ambil kolom yang jabatannya "Nilai Utama"
        return $this->record->recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $recap = $this->record;
        $recapType = $recap->recapType;

        // 2. TENTUKAN KOLOM YANG MAU DILIHAT TREN-NYA
        $targetName = $this->filter;

        // Jika user belum pilih, ambil metric pertama (biasanya Total Harga/Orderan)
        if (!$targetName) {
            $firstColumn = $recapType->recapColumns()
                ->where('role', 'metric_sum')
                ->orderBy('order') // Ambil urutan paling atas atau sesuaikan
                ->first();
            
            $targetName = $firstColumn ? $firstColumn->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        self::$heading = 'Tren: ' . $targetName;

        // 3. CARI KOLOM TANGGAL (Untuk Sumbu X)
        $dateColumn = $recapType->recapColumns()->where('type', 'date')->first();
        
        $rows = $recap->recapRows()->get();
        
        $labels = [];
        $dataPoints = [];

        foreach ($rows as $index => $row) {
            $dataJSON = $row->data;
            $flatData = Arr::dot($dataJSON);
            
            $yValue = 0;
            $xLabel = "Data #" . ($index + 1);

            foreach ($flatData as $key => $val) {
                // Cari Nilai Y (Angka)
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $cleanVal = str_replace(['Rp', 'IDR', '.', ' '], '', $val);
                    $cleanVal = str_replace(',', '.', $cleanVal);
                    if (is_numeric($cleanVal)) $yValue = (float) $cleanVal;
                }

                // Cari Nilai X (Tanggal)
                if ($dateColumn && Str::endsWith(strtolower($key), strtolower($dateColumn->name))) {
                    if (!empty($val)) {
                        try { 
                            $xLabel = Carbon::parse($val)->format('d M'); 
                        } catch (\Exception $e) {}
                    }
                }
            }

            $labels[] = $xLabel;
            $dataPoints[] = $yValue;
        }

        return [
            'datasets' => [
                [
                    'label' => $targetName,
                    'data' => $dataPoints,
                    'borderColor' => '#3b82f6',
                    'pointBackgroundColor' => '#3b82f6',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}