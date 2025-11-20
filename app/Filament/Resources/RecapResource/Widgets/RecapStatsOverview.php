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
        
        // 1. AMBIL METRIC SUM (Hanya kolom yang ditandai sebagai Nilai Utama)
        $targetColumns = $recap->recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->orderBy('order')
            ->get();

        $rows = $recap->recapRows()->get();
        $stats = []; 

        // --- A. LOGIKA PROGRESS WAKTU (VERSI BARU: Countdown Cerdas) ---
        $start = $recap->start_date ? Carbon::parse($recap->start_date)->startOfDay() : null;
        $end = $recap->end_date ? Carbon::parse($recap->end_date)->endOfDay() : null;
        $now = Carbon::now(); // Waktu sekarang

        if ($start && $end) {
            $totalDuration = $start->diffInDays($end) + 1;
            $isStarted = $now->greaterThanOrEqualTo($start);
            
            // Hitung selisih hari (bisa negatif jika lewat deadline)
            $diffDays = $now->startOfDay()->diffInDays($end->startOfDay(), false);

            $percent = 0;
            $chart = [];
            $color = 'success';
            $desc = '';

            if (!$isStarted) {
                // Kasus: Belum Mulai
                $percent = 0;
                $desc = "Mulai: " . $start->format('d M Y');
                $color = 'gray';
                $chart = [0, 0, 0, 0];
            } elseif ($diffDays < 0) {
                // Kasus: Sudah Lewat / Expired
                $percent = 100;
                $daysLate = abs($diffDays);
                $desc = "Selesai " . $end->format('d M') . " (Lewat {$daysLate} hari)";
                $color = 'danger'; // Merah karena telat
                $chart = [100, 100, 100, 100];
            } else {
                // Kasus: Sedang Berjalan
                $daysPassed = $start->diffInDays($now) + 1;
                $percent = min(100, round(($daysPassed / $totalDuration) * 100));
                
                // Format Tanggal Deadline
                $deadlineStr = $end->format('d M Y');
                
                if ($diffDays == 0) {
                    $desc = "Berakhir HARI INI ({$deadlineStr})";
                    $color = 'danger'; // Merah (Urgent)
                } elseif ($diffDays == 1) {
                    $desc = "Berakhir BESOK ({$deadlineStr})";
                    $color = 'warning'; // Kuning (Hati-hati)
                } else {
                    $desc = "Sisa {$diffDays} hari (Sampai {$deadlineStr})";
                    // Warna dinamis berdasarkan persentase progress
                    if ($percent > 90) $color = 'danger'; 
                    elseif ($percent > 75) $color = 'warning'; 
                    else $color = 'success'; 
                }

                // Visualisasi Chart Progress
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
            // Fallback jika tanggal belum diatur
            $stats[] = Stat::make('Progress Periode', '-')
                ->description('Atur Tanggal Mulai & Selesai')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray');
        }

        // --- B. LOGIKA STATISTIK LAINNYA (DENGAN PRIVACY MODE) ---
        
        // Cek status sensor dari session
        $isPrivacyMode = session()->get('privacy_mode', false);

        foreach ($targetColumns as $column) {
            $totalValue = 0;
            $chartData = []; 
            
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                $foundValue = 0; 

                foreach ($flatData as $key => $val) {
                    // Cek kecocokan nama kolom (Case Insensitive)
                    if (Str::endsWith(strtolower($key), strtolower($column->name))) {
                        $cleanVal = str_replace(['Rp', 'IDR', '.', ' '], '', $val);
                        $cleanVal = str_replace(',', '.', $cleanVal);
                        if (is_numeric($cleanVal)) $foundValue = (float) $cleanVal;
                        break; 
                    }
                }
                $totalValue += $foundValue;
                $chartData[] = $foundValue; 
            }

            // Format Angka vs Sensor
            if ($isPrivacyMode) {
                $formattedTotal = '******';
                $chartData = []; // Sembunyikan grafik kecil juga biar rahasia
            } else {
                if ($column->type === 'money' || Str::contains(strtolower($column->name), ['harga', 'biaya', 'total', 'rp'])) {
                    $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                    $icon = 'heroicon-m-banknotes';
                } else {
                    $formattedTotal = number_format($totalValue, 0, ',', '.');
                    $icon = 'heroicon-m-calculator';
                }
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