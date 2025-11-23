<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\RecapHelper; // Helper aktif

class RecapTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Data';
    protected static ?string $maxHeight = '150px';
    protected int | string | array $columnSpan = 'full';
    
    public ?Model $record = null;
    public ?string $filter = null; 

    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

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

        $targetName = $this->filter;

        if (!$targetName) {
            $firstColumn = $recapType->recapColumns()
                ->where('role', 'metric_sum')
                ->orderBy('order')
                ->first();
            $targetName = $firstColumn ? $firstColumn->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        self::$heading = 'Tren: ' . $targetName;

        $dateColumn = $recapType->recapColumns()->where('type', 'date')->first();
        
        $labels = [];
        $dataPoints = [];
        $index = 1;

        // OPTIMASI: Gunakan cursor()
        foreach ($recap->recapRows()->cursor() as $row) {
            $dataJSON = $row->data;
            if(is_string($dataJSON)) $dataJSON = json_decode($dataJSON, true);
            
            $flatData = Arr::dot($dataJSON ?? []);
            
            $yValue = 0;
            $xLabel = "Data #" . $index;

            foreach ($flatData as $key => $val) {
                // Cari Nilai Y (Angka) - Gunakan Helper
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $yValue = RecapHelper::cleanNumber($val);
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
            $index++;
        }

        // Sampling jika data terlalu banyak agar chart tidak crash
        if (count($dataPoints) > 100) {
            $step = ceil(count($dataPoints) / 100); // Ambil maksimal 100 titik
            $newLabels = [];
            $newDataPoints = [];
            for ($i = 0; $i < count($dataPoints); $i += $step) {
                $newLabels[] = $labels[$i] ?? '';
                $newDataPoints[] = $dataPoints[$i] ?? 0;
            }
            $labels = $newLabels;
            $dataPoints = $newDataPoints;
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