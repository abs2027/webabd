<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecapCostByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Proporsi Biaya';
    
    // Atur Max Height agar tidak terlalu tinggi
    protected static ?string $maxHeight = '150px';
    
    public ?Model $record = null;
    public ?string $filter = null; 

    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        return $this->record->recapType->recapColumns()
            ->whereIn('type', ['select', 'text']) 
            ->where('name', 'not like', '%Harga%')
            ->where('name', 'not like', '%Total%')
            ->where('name', 'not like', '%Amount%')
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) return ['datasets' => [], 'labels' => []];

        $recap = $this->record;
        
        $moneyCol = $recap->recapType->recapColumns()
            ->whereIn('type', ['money', 'number'])
            ->where('is_summarized', true)
            ->first();

        if (!$moneyCol) {
             $moneyCol = $recap->recapType->recapColumns()
                ->where(function($q) {
                    $q->where('name', 'like', '%Total%')
                      ->orWhere('name', 'like', '%Harga%');
                })
                ->first();
        }

        if (!$moneyCol) {
            return ['datasets' => [], 'labels' => []];
        }

        $targetName = $this->filter;

        if ($targetName && (str_contains($targetName, 'Harga') || str_contains($targetName, 'Total'))) {
            $targetName = null;
        }
        
        if (!$targetName) {
            $defaultCol = $recap->recapType->recapColumns()
                ->where('name', 'Tempat') 
                ->first();
            
            if (!$defaultCol) {
                $defaultCol = $recap->recapType->recapColumns()
                    ->where('name', 'Shift') 
                    ->first();
            }

            if (!$defaultCol) {
                $defaultCol = $recap->recapType->recapColumns()
                    ->whereIn('type', ['select', 'text'])
                    ->where('name', 'not like', '%Harga%')
                    ->where('name', 'not like', '%Total%')
                    ->orderBy('order')
                    ->first();
            }
            $targetName = $defaultCol ? $defaultCol->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        self::$heading = "Biaya per: " . $targetName;

        $sums = [];
        $rows = $recap->recapRows()->get();

        foreach ($rows as $row) {
            $flatData = Arr::dot($row->data ?? []);
            
            $label = 'Lainnya'; 
            $amount = 0;

            foreach ($flatData as $key => $value) {
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $label = $value ?: 'Tanpa Nama';
                }
                
                if (Str::endsWith(strtolower($key), strtolower($moneyCol->name))) {
                    $cleanVal = str_replace(['Rp', 'IDR', '.', ' '], '', $value);
                    $cleanVal = str_replace(',', '.', $cleanVal); 
                    if (is_numeric($cleanVal)) $amount = (float) $cleanVal;
                }
            }

            if (!isset($sums[$label])) {
                $sums[$label] = 0;
            }
            $sums[$label] += $amount;
        }

        $sums = array_filter($sums, fn($val) => $val > 0);
        arsort($sums); 

        return [
            'datasets' => [
                [
                    'label' => 'Total (Rp)',
                    'data' => array_values($sums),
                    'backgroundColor' => [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
                        '#8b5cf6', '#ec4899', '#6366f1', '#84cc16',
                    ],
                    'borderWidth' => 0, 
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => array_keys($sums),
        ];
    }

    protected static ?array $options = [
        'scales' => [
            'x' => ['display' => false],
            'y' => ['display' => false],
        ],
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'right', 
            ],
        ],
        'cutout' => '60%',
        'maintainAspectRatio' => false, // Kunci agar tidak gepeng
        'layout' => [
            'padding' => 10
        ]
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }
}