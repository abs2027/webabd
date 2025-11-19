<?php

namespace App\Filament\Resources\RecapResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class RecapDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Proporsi Kategori';
    protected static ?string $maxHeight = '300px';
    
    public ?Model $record = null;

    // Properti bawaan Filament untuk menyimpan pilihan Filter
    public ?string $filter = null; 

    // ▼▼▼ BAGIAN BARU: ISI FILTER DENGAN KOLOM TIPE 'SELECT' ▼▼▼
    protected function getFilters(): ?array
    {
        if (!$this->record) return [];

        // Ambil semua kolom yang tipenya 'select' (Pilihan/Dropdown)
        // Karena grafik Proporsi/Distribusi hanya masuk akal untuk data Kategori
        return $this->record->recapType->recapColumns()
            ->where('type', 'select')
            ->orderBy('order')
            ->pluck('name', 'name') // Key=Nama, Label=Nama
            ->toArray();
    }

    // Konfigurasi Bar Chart (Horizontal)
    protected static ?array $options = [
        'indexAxis' => 'y', 
        'plugins' => [
            'legend' => [
                'display' => false, 
            ],
        ],
        'scales' => [
            'x' => [
                'display' => true, 
                'grid' => [
                    'display' => false,
                ],
            ],
            'y' => [
                'display' => true, 
                'grid' => [
                    'display' => false,
                ],
            ],
        ],
        'maintainAspectRatio' => false,
    ];

    protected function getData(): array
    {
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $recap = $this->record;
        $recap->load('recapType');
        $recapType = $recap->recapType;

        // ▼▼▼ LOGIKA DINAMIS SEPERTI TREND CHART ▼▼▼
        
        // 1. Cek apakah ada Filter yang dipilih User
        $targetName = $this->filter;

        // 2. Jika kosong (awal buka), ambil kolom 'select' pertama
        if (!$targetName) {
            $firstCol = $recapType->recapColumns()
                ->where('type', 'select')
                ->orderBy('order')
                ->first();
            
            $targetName = $firstCol ? $firstCol->name : null;
        }

        if (!$targetName) {
            return ['datasets' => [], 'labels' => []];
        }
        
        // Update Judul agar user tahu sedang lihat data apa
        self::$heading = 'Proporsi: ' . $targetName;

        // 3. Hitung Data berdasarkan Nama Kolom yang dipilih
        $rows = $recap->recapRows()->get();
        $distribution = [];

        foreach ($rows as $row) {
            $dataJSON = $row->data;
            $flatData = Arr::dot($dataJSON);
            
            foreach ($flatData as $key => $val) {
                // Cek apakah key JSON berakhiran dengan nama kolom target
                if (str_ends_with($key, $targetName)) {
                    $label = $val ?: 'Tidak Ada Data'; // Handle jika kosong
                    
                    if (!isset($distribution[$label])) {
                        $distribution[$label] = 0;
                    }
                    $distribution[$label]++; 
                    break;
                }
            }
        }

        $labels = array_keys($distribution);
        $dataPoints = array_values($distribution);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => $dataPoints,
                    'backgroundColor' => '#3b82f6', 
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                    'borderRadius' => 4, 
                    'barThickness' => 20, 
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; 
    }
}