<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecapCostByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Proporsi Biaya';
    protected static ?string $maxHeight = '150px';
    
    public ?Model $record = null;
    public ?string $filter = null; 

    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        // ▼▼▼ LOGIKA BARU: AMBIL BERDASARKAN ROLE 'DIMENSION' ▼▼▼
        // Sistem sekarang hanya mengambil kolom yang Anda tandai sebagai "Kategori"
        return $this->record->recapType->recapColumns()
            ->where('role', 'dimension') 
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) return ['datasets' => [], 'labels' => []];

        $recap = $this->record;
        $recapType = $recap->recapType;
        
        // 1. CARI METRIK UANG (VALUE)
        // Kita cari kolom yang Role-nya "Nilai (Sum)"
        // Prioritaskan yang namanya mengandung "Harga" atau "Total" untuk widget Biaya ini
        $moneyCol = $recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->where(function($q) {
                $q->where('name', 'like', '%Harga%')
                  ->orWhere('name', 'like', '%Total%')
                  ->orWhere('name', 'like', '%Biaya%')
                  ->orWhere('type', 'money');
            })
            ->first();

        // Fallback: Jika tidak ada yang namanya "Harga", ambil metric_sum pertama apapun itu
        if (!$moneyCol) {
             $moneyCol = $recapType->recapColumns()
                ->where('role', 'metric_sum')
                ->orderBy('order', 'desc') // Biasanya Total ada di paling bawah
                ->first();
        }

        if (!$moneyCol) {
            return ['datasets' => [], 'labels' => []];
        }

        // 2. CARI KATEGORI (LABEL)
        $targetName = $this->filter;
        
        if (!$targetName) {
            // Ambil kolom 'dimension' pertama (Contoh: Tempat / Shift)
            $defaultCol = $recapType->recapColumns()
                ->where('role', 'dimension')
                ->orderBy('order')
                ->first();
            
            $targetName = $defaultCol ? $defaultCol->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        self::$heading = "Proporsi " . $moneyCol->name . " per " . $targetName;

        // 3. HITUNG DATA
        $sums = [];
        $rows = $recap->recapRows()->get();

        foreach ($rows as $row) {
            $flatData = Arr::dot($row->data ?? []);
            
            $label = 'Lainnya'; 
            $amount = 0;

            foreach ($flatData as $key => $value) {
                // Cek Label (Dimension)
                if (Str::endsWith(strtolower($key), strtolower($targetName))) {
                    $label = $value ?: 'Tanpa Nama';
                }
                
                // Cek Nilai (Metric Sum)
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
                    'label' => $moneyCol->name,
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
        'maintainAspectRatio' => false,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }
}