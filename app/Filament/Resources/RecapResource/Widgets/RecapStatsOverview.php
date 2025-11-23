<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\RecapHelper; 

class RecapStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) return [];

        $recap = $this->record;
        $stats = []; 

        // ---------------------------------------------------------
        // 1. WIDGET PROGRESS (Countdown)
        // ---------------------------------------------------------
        $start = $recap->start_date ? Carbon::parse($recap->start_date)->startOfDay() : null;
        $end = $recap->end_date ? Carbon::parse($recap->end_date)->endOfDay() : null;
        $now = Carbon::now(); 

        if ($start && $end) {
            $totalDuration = $start->diffInDays($end) + 1;
            $isStarted = $now->greaterThanOrEqualTo($start);
            $diffDays = $now->startOfDay()->diffInDays($end->startOfDay(), false);
            $percent = ($isStarted && $diffDays >= 0) ? min(100, round(($start->diffInDays($now) + 1) / $totalDuration * 100)) : ($diffDays < 0 ? 100 : 0);
            
            $desc = !$isStarted ? "Mulai: " . $start->format('d M Y') : ($diffDays < 0 ? "Selesai (Lewat " . abs($diffDays) . " hari)" : "Sisa {$diffDays} hari");
            $color = ($diffDays < 0 || ($percent > 90 && $diffDays >= 0)) ? 'danger' : (($percent > 75) ? 'warning' : 'success');
            if (!$isStarted) $color = 'gray';

            $chart = [];
            for ($i=0; $i<=10; $i++) { $chart[] = ($i * 10) <= $percent ? ($i * 10) : null; }
            
            $stats[] = Stat::make('Progress Periode', $percent . '%')->description($desc)->descriptionIcon('heroicon-m-clock')->chart($chart)->color($color);
        } else {
            $stats[] = Stat::make('Progress Periode', '-')->description('Atur Tanggal')->color('gray');
        }

        // ---------------------------------------------------------
        // 2. PERSIAPAN DATA
        // ---------------------------------------------------------
        $isPrivacyMode = session()->get('privacy_mode', false);
        
        $allMetricColumns = $recap->recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->orderBy('order')
            ->get();

        $colDebit = $allMetricColumns->first(fn($c) => Str::contains(strtolower($c->name), ['debit', 'masuk', 'pemasukan', 'income']));
        $colCredit = $allMetricColumns->first(fn($c) => Str::contains(strtolower($c->name), ['credit', 'kredit', 'keluar', 'pengeluaran', 'expense']));
        
        $columnsData = [];
        foreach ($allMetricColumns as $col) {
            $columnsData[$col->name] = [
                'total' => 0,
                'chart' => [],
                'obj' => $col,
                'half1' => 0, 
                'half2' => 0
            ];
        }

        $chartTotalSaldo = []; 
        $totalSaldoAkhir = 0;

        $totalRows = $recap->recapRows()->count();
        $chartStep = $totalRows > 50 ? floor($totalRows / 50) : 1;
        $halfPoint = floor($totalRows / 2);
        $rowIndex = 0;

        // ---------------------------------------------------------
        // 3. SINGLE PASS LOOP
        // ---------------------------------------------------------
        foreach ($recap->recapRows()->orderBy('id')->cursor() as $row) {
            $dataJSON = $row->data;
            if (is_string($dataJSON)) $dataJSON = json_decode($dataJSON, true) ?? [];

            $valDebit = $colDebit ? RecapHelper::getNumericValue($dataJSON, $colDebit->name) : 0;
            $valCredit = $colCredit ? RecapHelper::getNumericValue($dataJSON, $colCredit->name) : 0;
            
            $valSaldo = $valDebit - $valCredit;
            $totalSaldoAkhir += $valSaldo;

            foreach ($columnsData as $colName => &$info) {
                $val = RecapHelper::getNumericValue($dataJSON, $colName);
                $info['total'] += $val;

                if ($rowIndex < $halfPoint) {
                    $info['half1'] += $val;
                } else {
                    $info['half2'] += $val;
                }
                
                if (!$isPrivacyMode && ($rowIndex % $chartStep === 0)) {
                    $info['chart'][] = $val;
                }
            }

            if (!$isPrivacyMode && ($rowIndex % $chartStep === 0)) {
                $chartTotalSaldo[] = $totalSaldoAkhir;
            }
            
            $rowIndex++;
        }

        // ---------------------------------------------------------
        // 4. RENDER WIDGET
        // ---------------------------------------------------------
        
        // Helper Tren: TEKS LEBIH PENDEK (VISUAL CLEAN)
        $getTrendDesc = function($val1, $val2, $isExpense = false) {
            if ($val1 == 0) return ['text' => 'Data baru', 'icon' => 'heroicon-m-sparkles', 'color' => 'primary'];
            
            $diff = $val2 - $val1; 
            $percent = round(($diff / $val1) * 100);
            $isUp = $percent > 0;
            
            $icon = $isUp ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
            
            // Logika Warna: Expense Naik = Merah, Income Naik = Hijau
            $color = $isUp ? ($isExpense ? 'danger' : 'success') : ($isExpense ? 'success' : 'danger');
            
            // TEXT PENDEK: Agar tidak bertumpuk
            $text = ($isUp ? "+" : "") . $percent . "%";
            
            return ['text' => $text, 'icon' => $icon, 'color' => $color];
        };

        // A. Render Widget Kolom Asli
        foreach ($columnsData as $colName => $info) {
            if (Str::contains(strtolower($colName), ['total', 'saldo']) && $colDebit && $colCredit) {
                continue; 
            }

            $totalValue = $info['total'];
            $chartData = $info['chart'];
            $column = $info['obj'];

            if ($isPrivacyMode) {
                $formattedTotal = '******'; $chartData = [];
            } else {
                if ($column->type === 'money' || Str::contains(strtolower($colName), ['harga', 'biaya', 'total', 'rp', 'debit', 'credit', 'saldo'])) {
                    $formattedTotal = 'Rp ' . number_format($totalValue, 0, ',', '.');
                } else {
                    $formattedTotal = number_format($totalValue, 0, ',', '.');
                }
            }

            $isExpense = Str::contains(strtolower($colName), ['kredit', 'credit', 'keluar', 'expense', 'rugi']);
            $trend = $getTrendDesc($info['half1'], $info['half2'], $isExpense);

            $stats[] = Stat::make($colName, $formattedTotal)
                ->description($trend['text'])
                ->descriptionIcon($trend['icon'])
                ->color($trend['color'])
                ->chart($chartData); 
        }

        // B. Render Widget Saldo Akhir
        if ($colDebit && $colCredit) {
            $formattedSaldo = $isPrivacyMode ? '******' : 'Rp ' . number_format($totalSaldoAkhir, 0, ',', '.');
            $saldoColor = $totalSaldoAkhir >= 0 ? 'success' : 'danger';
            $saldoIcon = $totalSaldoAkhir >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-exclamation-triangle';
            $saldoDesc = $totalSaldoAkhir >= 0 ? 'Profit' : 'Defisit';

            $stats[] = Stat::make('Sisa Saldo (Total)', $formattedSaldo)
                ->description($saldoDesc)
                ->descriptionIcon($saldoIcon)
                ->color($saldoColor)
                ->chart($isPrivacyMode ? [] : $chartTotalSaldo);
        }

        return $stats;
    }
}