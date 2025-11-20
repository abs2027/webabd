<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
        $recap->load('recapType');
        
        // ▼▼▼ 1. UPDATE: AMBIL BERDASARKAN ROLE 'METRIC_SUM' ▼▼▼
        $targetColumns = $recap->recapType->recapColumns()
            ->where('role', 'metric_sum') // Hanya ambil yang jabatannya Nilai Utama
            ->orderBy('order')
            ->get();
        // ▲▲▲ SELESAI UPDATE ▲▲▲

        $rows = $recap->recapRows()->get();
        $stats = []; 

        // --- A. LOGIKA PROGRESS WAKTU (TETAP SAMA / SUDAH BAGUS) ---
        $start = $recap->start_date ? Carbon::parse($recap->start_date)->startOfDay() : null;
        $end = $recap->end_date ? Carbon::parse($recap->end_date)->endOfDay() : null;
        $now = Carbon::now();

        if ($start && $end) {
            $totalDays = $start->diffInDays($end) + 1; 
            $isStarted = $now->greaterThanOrEqualTo($start);
            $isFinished = $now->greaterThan($end);

            if (!$isStarted) {
                $percent = 0;
                $desc = "Dimulai tgl " . $start->format('d M');
                $color = 'gray';
                $chart = [0, 0, 0, 0];
            } elseif ($isFinished) {
                $percent = 100;
                $desc = "Periode Berakhir";
                $color = 'danger'; 
                $chart = [100, 100, 100, 100];
            } else {
                $daysPassed = $start->diffInDays($now) + 1;
                $remainingDays = round($now->diffInDays($end)); 

                $percent = min(100, round(($daysPassed / $totalDays) * 100));
                $desc = "Sisa {$remainingDays} hari lagi";
                
                if ($percent > 90) $color = 'danger'; 
                elseif ($percent > 75) $color = 'warning'; 
                else $color = 'success'; 

                $chart = [];
                for ($i=0; $i<=10; $i++) {
                    $chart[] = ($i * 10) <= $percent ? ($i * 10) : null; 
                }
            }

            $stats[] = Stat::make('Progress Periode', $percent . '%')
                ->description($desc)
                ->descriptionIcon('heroicon-m-clock')
                ->chart($chart)
                ->color($color);

        } else {
            $stats[] = Stat::make('Progress Periode', '-')
                ->description('Set tanggal mulai & selesai')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray');
        }

        // --- B. LOGIKA STATISTIK METRIK (DIPERBARUI AGAR LEBIH PINTAR) ---
        foreach ($targetColumns as $column) {
            $totalValue = 0;
            $chartData = []; 
            
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                $foundValue = 0; 

                foreach ($flatData as $key => $val) {
                    // Cek apakah key JSON berakhiran dengan nama kolom target
                    if (Str::endsWith(strtolower($key), strtolower($column->name))) {
                        $cleanVal = str_replace(['Rp', 'IDR', '.', ' '], '', $val);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        if (is_numeric($cleanVal)) {
                            $foundValue = (float) $cleanVal;
                        }
                        break; 
                    }
                }

                $totalValue += $foundValue;
                $chartData[] = $foundValue; 
            }

            // Format Tampilan
            if ($column->type === 'money' || Str::contains(strtolower($column->name), ['harga', 'biaya', 'total', 'rp'])) {
                $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-banknotes';
            } else {
                $formattedTotal = number_format($totalValue, 0, ',', '.');
                $icon = 'heroicon-m-calculator';
            }

            $label = (stripos($column->name, 'Total') === 0) ? $column->name : 'Total ' . $column->name;

            $stats[] = Stat::make($label, $formattedTotal)
                ->description('Akumulasi ' . $column->name)
                ->descriptionIcon($icon)
                ->color('success')
                ->chart($chartData); 
        }

        return $stats;
    }
}