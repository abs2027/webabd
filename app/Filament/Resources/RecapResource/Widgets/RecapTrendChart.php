<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class RecapTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Data Rekapitulasi';
    protected static ?string $maxHeight = '300px';
    
    public ?Model $record = null;

    // Properti bawaan Filament untuk menyimpan pilihan Filter saat ini
    public ?string $filter = null; 

    // ▼▼▼ BAGIAN BARU: MENGISI OPSI DROPDOWN ▼▼▼
    protected function getFilters(): ?array
    {
        // Pastikan record ada
        if (!$this->record) return [];

        // Ambil semua kolom yang: 
        // 1. Tipe Angka/Uang 
        // 2. Fitur Summary-nya AKTIF
        return $this->record->recapType->recapColumns()
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order')
            ->pluck('name', 'name') // Key=Nama, Label=Nama
            ->toArray();
    }

    protected function getData(): array
    {
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $recap = $this->record;
        $recapType = $recap->recapType;

        // ▼▼▼ LOGIKA PEMILIHAN KOLOM YANG LEBIH PINTAR ▼▼▼
        
        // 1. Cek apakah User sedang memilih sesuatu di Dropdown ($this->filter)
        $targetName = $this->filter;

        // 2. Jika TIDAK ada yang dipilih (awal loading), ambil kolom pertama sebagai default
        if (!$targetName) {
            $firstColumn = $recapType->recapColumns()
                ->whereIn('type', ['number', 'money'])
                ->where('is_summarized', true)
                ->orderBy('order')
                ->first();
            
            $targetName = $firstColumn ? $firstColumn->name : null;
        }

        // Jika masih tidak ada kolom (misal belum bikin kolom summary), return kosong
        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }

        // Update Judul Chart agar dinamis sesuai pilihan
        self::$heading = 'Tren: ' . $targetName;

        // 3. Ambil Data Baris & Kolom Tanggal
        $dateColumn = $recapType->recapColumns()->where('type', 'date')->first();
        $rows = $recap->recapRows()->get();
        
        $labels = [];
        $dataPoints = [];

        foreach ($rows as $index => $row) {
            $dataJSON = $row->data;
            $yValue = 0;
            $flatData = Arr::dot($dataJSON);
            
            // --- LOGIKA PENCARIAN NILAI ---
            foreach ($flatData as $key => $val) {
                // Cek apakah key mengandung NAMA YANG DIPILIH ($targetName)
                if (str_ends_with($key, $targetName)) {
                    $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                    $cleanVal = str_replace(',', '.', $cleanVal);
                    $yValue = (float) $cleanVal;
                    break;
                }
            }
            
            // --- LOGIKA LABEL TANGGAL (Sama seperti sebelumnya) ---
            $xLabel = "Data #" . ($index + 1);
            if ($dateColumn) {
                foreach ($flatData as $key => $val) {
                    if (str_ends_with($key, $dateColumn->name) && !empty($val)) {
                        try { $xLabel = Carbon::parse($val)->format('d M'); } catch (\Exception $e) {}
                        break;
                    }
                }
            }

            $labels[] = $xLabel;
            $dataPoints[] = $yValue;
        }

        return [
            'datasets' => [
                [
                    'label' => $targetName, // Label garis sesuai pilihan dropdown
                    'data' => $dataPoints,
                    'borderColor' => '#3b82f6',
                    'pointBackgroundColor' => '#3b82f6',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}