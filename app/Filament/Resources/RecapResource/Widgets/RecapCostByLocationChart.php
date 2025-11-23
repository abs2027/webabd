<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Helpers\RecapHelper; // Helper aktif

class RecapCostByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Proporsi Biaya';
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
                ->whereIn('type', ['select', 'text'])
                ->where('name', 'not like', '%Harga%')
                ->where('name', 'not like', '%Total%')
                ->orderBy('order')
                ->first();
            $targetName = $defaultCol ? $defaultCol->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        self::$heading = "Biaya per: " . $targetName;

        $sums = [];
        
        // OPTIMASI: Gunakan cursor() untuk looping hemat memori
        foreach ($recap->recapRows()->cursor() as $row) {
            $data = $row->data;
            if(is_string($data)) $data = json_decode($data, true);
            
            $flatData = Arr::dot($data ?? []);
            
            $label = 'Lainnya'; 
            $amount = 0;

            foreach ($flatData as $key => $value) {
                // Cari Label Kategori
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $label = $value ?: 'Tanpa Nama';
                }
                
                // Cari Nilai Uang (Gunakan Helper)
                if (Str::endsWith(strtolower($key), strtolower($moneyCol->name))) {
                    $amount = RecapHelper::cleanNumber($value);
                }
            }

            if (!isset($sums[$label])) {
                $sums[$label] = 0;
            }
            $sums[$label] += $amount;
        }

        $sums = array_filter($sums, fn($val) => $val > 0);
        arsort($sums); 
        
        // Batasi irisan donut agar tidak terlalu penuh (Top 10 saja)
        if (count($sums) > 10) {
            $top10 = array_slice($sums, 0, 10, true);
            $others = array_slice($sums, 10, null, true);
            $top10['Lainnya'] = array_sum($others);
            $sums = $top10;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total (Rp)',
                    'data' => array_values($sums),
                    'backgroundColor' => [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
                        '#8b5cf6', '#ec4899', '#6366f1', '#84cc16',
                        '#06b6d4', '#f97316'
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
        'maintainAspectRatio' => false,
        'layout' => [
            'padding' => 10
        ]
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }
}