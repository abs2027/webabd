<?php

namespace App\Filament\Resources\RecapTypeResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;

class CompareRecapsDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Proporsi Kategori';
    protected static ?string $maxHeight = '300px';

    public ?Model $record = null; 
    public ?string $filter = null; 
    public array $periodIds = [];

    #[On('update-chart-periods')]
    public function updateChartPeriods(array $periodIds): void
    {
        $this->periodIds = $periodIds;
        $this->updateChartData(); 
    }

    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        return $this->record->recapColumns()
            ->where('type', 'select')
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) return ['datasets' => [], 'labels' => []];

        // A. Tentukan Kolom Target
        $targetName = $this->filter;
        if (!$targetName) {
            $firstCol = $this->record->recapColumns()
                ->where('type', 'select')
                ->orderBy('order')
                ->first();
            $targetName = $firstCol ? $firstCol->name : null;
        }

        if (!$targetName) return ['datasets' => [], 'labels' => []];

        self::$heading = 'Perbandingan Proporsi: ' . $targetName;

        // B. Ambil Data Periode
        if (!empty($this->periodIds)) {
            $recaps = $this->record->recaps()
                ->whereIn('id', $this->periodIds)
                ->orderBy('start_date')
                ->get();
        } else {
            $recaps = $this->record->recaps()
                // ▼▼▼ PERBAIKAN: HANYA AMBIL YANG ADA DATANYA ▼▼▼
                ->has('recapRows')
                ->latest('id')
                ->take(2) 
                // ▲▲▲ SELESAI ▲▲▲
                ->get();
        }

        // C. LOGIKA AGREGASI DATA
        $allCategories = [];
        
        // Step 1: Kumpulkan Kategori Unik
        foreach ($recaps as $recap) {
            if ($recap->recapRows->count() === 0) continue; 

            foreach ($recap->recapRows as $row) {
                $flatData = Arr::dot($row->data ?? []);
                foreach ($flatData as $k => $v) {
                    if (str_ends_with($k, $targetName) && !empty($v)) {
                        $allCategories[$v] = true;
                        break;
                    }
                }
            }
        }
        
        $labels = array_keys($allCategories);
        sort($labels); 

        // Step 2: Hitung Count
        $datasets = [];
        $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1', '#ec4899'];
        $colorIndex = 0;

        foreach ($recaps as $recap) {
            if ($recap->recapRows->count() === 0) continue;

            $counts = array_fill_keys($labels, 0);

            foreach ($recap->recapRows as $row) {
                $flatData = Arr::dot($row->data ?? []);
                foreach ($flatData as $k => $v) {
                    if (str_ends_with($k, $targetName) && !empty($v)) {
                        if (isset($counts[$v])) {
                            $counts[$v]++;
                        }
                        break;
                    }
                }
            }

            $datasets[] = [
                'label' => $recap->name,
                'data' => array_values($counts),
                'backgroundColor' => $colors[$colorIndex % count($colors)],
                'borderColor' => $colors[$colorIndex % count($colors)],
                'borderWidth' => 1,
            ];
            $colorIndex++;
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'bar'; }
    protected static ?array $options = [
        'indexAxis' => 'y', 
        'scales' => [
            'x' => ['stacked' => false, 'grid' => ['display' => false]],
            'y' => ['stacked' => false, 'grid' => ['display' => false]],
        ],
        'plugins' => ['legend' => ['position' => 'bottom']]
    ];
}