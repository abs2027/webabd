<?php

namespace App\Filament\Resources\RecapTypeResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Livewire\Attributes\On;

class CompareRecapsChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Tren Akumulasi (Progress)';
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
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) return ['datasets' => [], 'labels' => []];

        // A. LOGIKA FILTER KOLOM
        $targetName = $this->filter;
        if (!$targetName) {
            $firstCol = $this->record->recapColumns()
                ->whereIn('type', ['number', 'money'])
                ->where('is_summarized', true)
                ->first();
            $targetName = $firstCol ? $firstCol->name : null;
        }
        if (!$targetName) return ['datasets' => [], 'labels' => []];

        self::$heading = 'Trend Progress: ' . $targetName;

        $dateColumn = $this->record->recapColumns()->where('type', 'date')->first();
        if (!$dateColumn) return ['datasets' => [], 'labels' => []]; 

        // B. LOGIKA PERIODE (FILTER vs DEFAULT)
        if (!empty($this->periodIds)) {
            $recaps = $this->record->recaps()->whereIn('id', $this->periodIds)->orderBy('start_date')->get();
        } else {
            $recaps = $this->record->recaps()
                // ▼▼▼ PERBAIKAN: HANYA AMBIL YANG ADA DATANYA ▼▼▼
                ->has('recapRows') 
                ->latest('id') 
                ->take(2) 
                // ▲▲▲ SELESAI ▲▲▲
                ->get();
        }

        $datasets = [];
        $globalMaxDays = 0; 
        $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1', '#ec4899'];
        $colorIndex = 0;

        foreach ($recaps as $recap) {
            // C. LOGIKA DATA HARIAN & PROGRESS
            $allDates = [];
            foreach ($recap->recapRows as $row) {
                 $flatData = Arr::dot($row->data ?? []);
                 foreach ($flatData as $k => $v) {
                    if (str_ends_with($k, $dateColumn->name) && !empty($v)) {
                        try { $allDates[] = Carbon::parse($v)->startOfDay(); } catch (\Exception $e) {}
                        break;
                    }
                }
            }
            if (empty($allDates)) continue;
            $minDate = min($allDates); 
            $maxDateInRecap = max($allDates);
            
            $duration = $minDate->diffInDays($maxDateInRecap);
            if ($duration > $globalMaxDays) $globalMaxDays = $duration;

            $dailyData = array_fill(0, $duration + 1, 0);

            foreach ($recap->recapRows as $row) {
                $flatData = Arr::dot($row->data ?? []);
                $yValue = 0;
                foreach ($flatData as $k => $v) {
                    if (str_ends_with($k, $targetName)) {
                        $cleanVal = str_replace(['Rp', '.', ' '], '', $v);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        $yValue = (float) $cleanVal;
                        break;
                    }
                }
                foreach ($flatData as $k => $v) {
                    if (str_ends_with($k, $dateColumn->name) && !empty($v)) {
                        try { 
                            $currentDate = Carbon::parse($v)->startOfDay();
                            $dayIndex = $minDate->diffInDays($currentDate);
                            if (isset($dailyData[$dayIndex])) {
                                $dailyData[$dayIndex] += $yValue;
                            }
                        } catch (\Exception $e) {}
                        break;
                    }
                }
            }

            $cumulativeData = [];
            $runningTotal = 0;
            for ($i = 0; $i <= $duration; $i++) {
                $val = $dailyData[$i] ?? 0;
                $runningTotal += $val; 
                $cumulativeData[] = ['x' => $i + 1, 'y' => $runningTotal];
            }

            $datasets[] = [
                'label' => $recap->name,
                'data' => $cumulativeData,
                'borderColor' => $colors[$colorIndex % count($colors)],
                'backgroundColor' => $colors[$colorIndex % count($colors)],
                'fill' => false,
                'tension' => 0.4,
                'pointRadius' => 0, 
                'pointHoverRadius' => 6,
                'borderWidth' => 2,
            ];
            $colorIndex++;
        }

        $labels = range(1, $globalMaxDays + 1);

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string { return 'line'; }
    protected static ?array $options = [
        'plugins' => [
            'legend' => ['position' => 'bottom'],
            'tooltip' => [
                'mode' => 'index', 
                'intersect' => false,
                'callbacks' => ['title' => "function(context) { return 'Hari ke-' + context[0].label; }"]
            ],
        ],
        'scales' => [
            'x' => ['title' => ['display' => true, 'text' => 'Hari ke-N (Progress Proyek)']],
        ],
    ];
}