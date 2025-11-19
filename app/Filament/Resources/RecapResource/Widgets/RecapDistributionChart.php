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

    // Konfigurasi Bar Chart
    protected static ?array $options = [
        'indexAxis' => 'y', // 'y' = Bar Horizontal (Ke Samping), 'x' = Bar Vertikal (Ke Atas)
        'plugins' => [
            'legend' => [
                'display' => false, // Sembunyikan legend karena label sudah ada di sumbu Y
            ],
        ],
        'scales' => [
            'x' => [
                'display' => true, // Tampilkan angka di sumbu X (Jumlah)
                'grid' => [
                    'display' => false,
                ],
            ],
            'y' => [
                'display' => true, // Tampilkan label kategori di sumbu Y
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

        // 1. Cari Kolom Target (Tipe 'select')
        $targetColumn = $recapType->recapColumns()
            ->where('type', 'select')
            ->orderBy('order')
            ->first();

        if (!$targetColumn) {
            return ['datasets' => [], 'labels' => []];
        }
        
        self::$heading = 'Proporsi ' . $targetColumn->name;

        // 2. Hitung Data
        $rows = $recap->recapRows()->get();
        $distribution = [];

        foreach ($rows as $row) {
            $dataJSON = $row->data;
            $flatData = Arr::dot($dataJSON);
            
            foreach ($flatData as $key => $val) {
                if (str_ends_with($key, $targetColumn->name)) {
                    $label = $val ?: 'Tidak Ada Data';
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
                    // Warna Batang (Biru Filament)
                    'backgroundColor' => '#3b82f6', 
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 1,
                    'borderRadius' => 4, // Sudut tumpul biar manis
                    'barThickness' => 20, // Ketebalan batang
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Tipe Chart: Bar
    }
}