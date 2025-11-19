<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class RecapTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Data Rekapitulasi';
    
    // Atur tinggi maksimal chart agar tidak terlalu mendominasi layar
    protected static ?string $maxHeight = '300px';
    

    // Properti untuk menerima record dari halaman View
    public ?Model $record = null;

    protected function getData(): array
    {
        // Pastikan kita punya record Recap yang sedang dibuka
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $recap = $this->record;
        
        // Pastikan relasi recapType dimuat
        $recap->load('recapType');
        $recapType = $recap->recapType;

        // 1. Cari Kolom Target (Sumbu Y)
        // Kriteria: Tipe 'number' atau 'money' DAN fitur Summary aktif
        $targetColumn = $recapType->recapColumns()
            ->whereIn('type', ['number', 'money'])
            ->where('is_summarized', true)
            ->orderBy('order') // Ambil yang pertama urutannya
            ->first(); 

        // Jika tidak ada kolom yang cocok, kembalikan grafik kosong
        if (!$targetColumn) {
            return ['datasets' => [], 'labels' => []];
        }

        // 2. Cari Kolom Tanggal (Untuk Sumbu X)
        $dateColumn = $recapType->recapColumns()
            ->where('type', 'date')
            ->first();

        // 3. Ambil Data Baris
        $rows = $recap->recapRows()->get();
        
        $labels = [];
        $dataPoints = [];

        foreach ($rows as $index => $row) {
            $dataJSON = $row->data;
            
            // --- LOGIKA MENCARI NILAI Y ---
            $yValue = 0;
            // Flatten array JSON untuk memudahkan pencarian key
            $flatData = Arr::dot($dataJSON);
            
            // Cari value yang key-nya mengandung nama kolom target
            // Contoh key flattened: "data.Total Harga" atau "Informasi.Biaya"
            foreach ($flatData as $key => $val) {
                // Kita cek apakah key diakhiri dengan nama kolom target
                if (str_ends_with($key, $targetColumn->name)) {
                    // Bersihkan format uang (Rp, titik, koma) agar jadi float murni
                    // Contoh: "Rp 1.500.000" -> 1500000
                    $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                    $cleanVal = str_replace(',', '.', $cleanVal); // Ubah koma desimal jadi titik
                    $yValue = (float) $cleanVal;
                    break; // Ketemu, berhenti looping
                }
            }
            
            // --- LOGIKA MENCARI LABEL X ---
            $xLabel = "Data #" . ($index + 1); // Default: Nomor Urut
            
            if ($dateColumn) {
                foreach ($flatData as $key => $val) {
                    if (str_ends_with($key, $dateColumn->name) && !empty($val)) {
                        try {
                            $xLabel = Carbon::parse($val)->format('d M');
                        } catch (\Exception $e) {
                            // Jika format tanggal error, tetap pakai default
                        }
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
                    'label' => $targetColumn->name, // Judul garis sesuai nama kolom (misal: "Total Harga")
                    'data' => $dataPoints,
                    'borderColor' => '#3b82f6', // Warna Biru Filament (Primary-500)
                    'pointBackgroundColor' => '#3b82f6',
                    'fill' => 'start', // Arsir area di bawah garis
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Warna arsir transparan
                    'tension' => 0.3, // Kelengkungan garis (0 = lurus kaku, 0.4 = mulus)
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