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
        
        // Hanya ambil kolom yang diset sebagai "Nilai Utama" (Metric Sum)
        $targetColumns = $recap->recapType->recapColumns()
            ->where('role', 'metric_sum')
            ->orderBy('order')
            ->get();

        $rows = $recap->recapRows()->get();
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
                $deadlineStr = $end->format('d M Y');
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
            
            foreach ($rows as $row) {
                $dataJSON = $row->data;
                $flatData = Arr::dot($dataJSON);
                $foundValue = 0; 

                foreach ($flatData as $key => $val) {
                    // ▼▼▼ FILTER NAMA KOLOM (STRICT MODE) ▼▼▼
                    // Pecah key: "data.Group.Debit" -> ["data", "Group", "Debit"]
                    $keyParts = explode('.', $key);
                    $actualName = end($keyParts); // Ambil "Debit"

                    // Bandingkan "Debit" == "Debit" (Case Insensitive)
                    if (strtolower(trim($actualName)) === strtolower(trim($column->name))) {
                        
                        // Bersihkan Format Angka (Indonesia/US)
                        $valStr = (string) $val;
                        $valStr = preg_replace('/[^\d,.-]/', '', $valStr); 
                        
                        if ($valStr !== '') {
                            // Deteksi Ribuan Titik (Indo)
                            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $valStr)) { 
                                $foundValue = (float) str_replace('.', '', $valStr); 
                            }
                            // Deteksi Ribuan Koma (US)
                            elseif (preg_match('/^-?\d{1,3}(,\d{3})+$/', $valStr)) { 
                                $foundValue = (float) str_replace(',', '', $valStr); 
                            }
                            // Campuran
                            else {
                                $lastDot = strrpos($valStr, '.'); 
                                $lastComma = strrpos($valStr, ',');
                                if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                                    // Format Indo (Koma di belakang) -> Ganti Koma jadi Titik
                                    $valStr = str_replace('.', '', $valStr);
                                    $valStr = str_replace(',', '.', $valStr);
                                } else {
                                    // Format US (Titik di belakang) -> Hapus Koma
                                    $valStr = str_replace(',', '', $valStr);
                                }
                                $foundValue = (float) $valStr;
                            }
                        }
                        break; // Sudah ketemu untuk baris ini? Lanjut baris berikutnya.
                    }
                    // ▲▲▲ SELESAI FILTER ▲▲▲
                }
                $totalValue += $foundValue;
                $chartData[] = $foundValue; 
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