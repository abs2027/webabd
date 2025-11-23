<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\RecapHelper; // Gunakan Helper

class RecapStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $recap = $this->record;
        
        // Ambil kolom target (Metric Sum)
        $targetColumns = $recap->recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->orderBy('order')
            ->get();

        // OPTIMASI MEMORI: Gunakan pluck('data') untuk mengambil JSON-nya saja, bukan Model lengkap
        // Ini jauh lebih ringan daripada ->get()
        $dataCollection = $recap->recapRows()->pluck('data'); 

        $stats = []; 

        // --- LOGIKA PROGRESS (Countdown) ---
        $start = $recap->start_date ? Carbon::parse($recap->start_date)->startOfDay() : null;
        $end = $recap->end_date ? Carbon::parse($recap->end_date)->endOfDay() : null;
        $now = Carbon::now(); 

        if ($start && $end) {
            $totalDuration = $start->diffInDays($end) + 1;
            $isStarted = $now->greaterThanOrEqualTo($start);
            $diffDays = $now->startOfDay()->diffInDays($end->startOfDay(), false);

            $percent = 0;
            $chart = [];
            $color = 'success';
            $desc = '';

            if (!$isStarted) {
                $percent = 0; $desc = "Mulai: " . $start->format('d M Y'); $color = 'gray'; $chart = [0, 0, 0, 0];
            } elseif ($diffDays < 0) {
                $percent = 100; $daysLate = abs($diffDays); $desc = "Selesai " . $end->format('d M') . " (Lewat {$daysLate} hari)"; $color = 'danger'; $chart = [100, 100, 100, 100];
            } else {
                $daysPassed = $start->diffInDays($now) + 1;
                $percent = min(100, round(($daysPassed / $totalDuration) * 100));
                if ($diffDays == 0) { $desc = "Berakhir HARI INI"; $color = 'danger'; } 
                elseif ($diffDays == 1) { $desc = "Berakhir BESOK"; $color = 'warning'; } 
                else { $desc = "Sisa {$diffDays} hari"; if ($percent > 90) $color = 'danger'; elseif ($percent > 75) $color = 'warning'; else $color = 'success'; }
                for ($i=0; $i<=10; $i++) { $chart[] = ($i * 10) <= $percent ? ($i * 10) : null; }
            }
            $stats[] = Stat::make('Progress Periode', $percent . '%')->description($desc)->descriptionIcon('heroicon-m-clock')->chart($chart)->color($color);
        } else {
            $stats[] = Stat::make('Progress Periode', '-')->description('Atur Tanggal')->color('gray');
        }

        // --- LOGIKA UTAMA (PERHITUNGAN ANGKA) ---
        $isPrivacyMode = session()->get('privacy_mode', false);

        foreach ($targetColumns as $column) {
            $totalValue = 0;
            $chartData = []; 
            
            // Loop data tanpa membebani RAM (menggunakan dataCollection dari pluck di atas)
            foreach ($dataCollection as $dataJSON) {
                if (is_string($dataJSON)) $dataJSON = json_decode($dataJSON, true);
                
                // Gunakan Helper untuk mencari nilai angka di dalam JSON nested
                // Helper ini otomatis membersihkan Rp, Titik, Koma, dan Tanda Kurung
                $foundValue = RecapHelper::getNumericValue($dataJSON ?? [], $column->name);

                $totalValue += $foundValue;
                // Hanya simpan data chart jika tidak privacy mode (hemat memori array)
                if (!$isPrivacyMode) {
                    $chartData[] = $foundValue; 
                }
            }

            // Format Tampilan Angka Widget
            if ($isPrivacyMode) {
                $formattedTotal = '******'; $chartData = [];
            } else {
                if ($column->type === 'money' || Str::contains(strtolower($column->name), ['harga', 'biaya', 'total', 'rp', 'debit', 'credit'])) {
                    $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                    $icon = 'heroicon-m-banknotes';
                } else {
                    $formattedTotal = number_format($totalValue, 0, ',', '.');
                    $icon = 'heroicon-m-calculator';
                }
            }

            // Downsample chart data agar tidak terlalu berat merender ribuan titik di sparkline
            // Ambil maksimal 50 titik sampel
            if (count($chartData) > 50) {
                $step = floor(count($chartData) / 50);
                $chartData = array_filter($chartData, function($k) use ($step) { return $k % $step == 0; }, ARRAY_FILTER_USE_KEY);
                $chartData = array_values($chartData);
            }

            $label = (stripos($column->name, 'Total') === 0) ? $column->name : 'Total ' . $column->name;
            $stats[] = Stat::make($label, $formattedTotal)
                ->description('Akumulasi ' . $column->name)
                ->descriptionIcon($icon ?? 'heroicon-m-chart-bar')
                ->color('success')
                ->chart($chartData); 
        }

        return $stats;
    }
}