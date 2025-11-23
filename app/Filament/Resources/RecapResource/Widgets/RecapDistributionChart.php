<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecapDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Frekuensi Data';
    protected static ?string $maxHeight = '350px';
    
    public ?Model $record = null;
    public ?string $filter = null; 

    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        return $this->record->recapType->recapColumns()
            ->where('role', 'dimension')
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected static ?array $options = [
        'indexAxis' => 'y', 
        'plugins' => [
            'legend' => ['display' => false],
        ],
        'scales' => [
            'x' => ['display' => true, 'grid' => ['display' => false]],
            'y' => ['display' => true, 'grid' => ['display' => false]],
        ],
        'maintainAspectRatio' => false,
    ];

    protected function getData(): array
    {
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $recap = $this->record;
        $recapType = $recap->recapType;

        $targetName = $this->filter;

        if (!$targetName) {
            $firstCol = $recapType->recapColumns()
                ->where('role', 'dimension')
                ->orderBy('order')
                ->first();
            
            $targetName = $firstCol ? $firstCol->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }
        
        self::$heading = 'Frekuensi: ' . $targetName;

        // OPTIMASI: Gunakan cursor()
        $distribution = [];

        foreach ($recap->recapRows()->cursor() as $row) {
            $dataJSON = $row->data;
            if(is_string($dataJSON)) $dataJSON = json_decode($dataJSON, true);
            
            $flatData = Arr::dot($dataJSON ?? []);
            
            foreach ($flatData as $key => $val) {
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $label = $val ?: 'Tanpa Nama';
                    
                    if (!isset($distribution[$label])) {
                        $distribution[$label] = 0;
                    }
                    $distribution[$label]++;
                    break;
                }
            }
        }

        arsort($distribution); // Urutkan dari yang terbanyak
        
        // Batasi hanya Top 15 agar chart tidak kepanjangan
        if (count($distribution) > 15) {
            $distribution = array_slice($distribution, 0, 15, true);
        }

        $labels = array_keys($distribution);
        $dataPoints = array_values($distribution);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Baris Data',
                    'data' => $dataPoints,
                    'backgroundColor' => '#3b82f6', 
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                    'borderRadius' => 4, 
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; 
    }
}