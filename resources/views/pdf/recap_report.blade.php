<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Rekapitulasi - {{ $recap->name }}</title>
    
    <style>
        /* Menggunakan style dasar dari Invoice Anda */
        body { font-family: 'sans-serif'; font-size: 10px; color: #333; }
        .header-title { text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0; text-transform: uppercase; text-decoration: underline; }
        
        /* Table Style */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #555; padding: 5px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #eee; text-align: center; font-weight: bold; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer-info { margin-top: 20px; font-size: 9px; font-style: italic; }
    </style>
</head>
<body>

    <!-- Kop Surat (Menggunakan komponen Anda) -->
    @if(isset($company))
        <x-pdf.letterhead :company="$company" />
    @endif

    <div class="header-title">
        Laporan Rekapitulasi: {{ $recap->name }}
    </div>

    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td width="15%"><strong>Periode</strong></td>
            <td width="35%">: {{ $recap->start_date?->format('d M Y') }} s/d {{ $recap->end_date?->format('d M Y') }}</td>
            <td width="15%"><strong>Project</strong></td>
            <td width="35%">: {{ $recap->project->name ?? '-' }}</td>
        </tr>
    </table>

    <!-- TABEL DINAMIS -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <!-- Loop Header Kolom Dinamis -->
                @foreach($columns as $col)
                    <th>{{ $col->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    
                    <!-- Loop Data Baris per Baris -->
                    @foreach($columns as $col)
                        @php
                            // Logika pencarian data JSON (Sama seperti di Controller)
                            $value = $row->data;
                            $tempCol = $col;
                            $path = [];
                            
                            while ($tempCol != null) {
                                array_unshift($path, $tempCol->name);
                                $tempCol = $tempCol->parent;
                            }

                            foreach ($path as $key) {
                                $value = $value[$key] ?? null;
                            }
                            
                            // Format Tampilan Khusus PDF
                            $displayValue = $value;
                            if ($col->type == 'money' && is_numeric($value)) {
                                $displayValue = 'Rp ' . number_format($value, 0, ',', '.');
                            } elseif ($col->type == 'date' && $value) {
                                $displayValue = \Carbon\Carbon::parse($value)->format('d/m/Y');
                            }
                        @endphp

                        <td class="{{ in_array($col->type, ['number', 'money']) ? 'text-right' : '' }}">
                            {{ $displayValue ?? '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        
        <!-- FOOTER TOTAL (Dinamis juga) -->
        <tfoot>
            <tr>
                <td colspan="1" style="font-weight: bold; text-align: center;">TOTAL</td>
                @foreach($columns as $col)
                    @php
                        $total = 0;
                        $hasTotal = false;

                        if ($col->is_summarized) {
                            $hasTotal = true;
                            // Hitung manual di PHP karena data ada di memory view
                            foreach($rows as $r) {
                                // (Ulangi logika ambil value $val seperti di atas)
                                $val = $r->data;
                                $temp = $col;
                                $p = [];
                                while($temp) { array_unshift($p, $temp->name); $temp = $temp->parent; }
                                foreach($p as $k) { $val = $val[$k] ?? 0; }
                                
                                // Bersihkan format uang jika ada
                                $val = str_replace(['Rp', '.', ' '], '', $val);
                                $val = str_replace(',', '.', $val);
                                
                                $total += (float) $val;
                            }
                        }
                    @endphp

                    <td class="text-right" style="font-weight: bold; background-color: #f9f9f9;">
                        @if($hasTotal)
                            @if($col->type == 'money')
                                Rp {{ number_format($total, 0, ',', '.') }}
                            @else
                                {{ number_format($total, 0, ',', '.') }}
                            @endif
                        @endif
                    </td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <div class="footer-info">
        Dicetak pada: {{ now()->format('d F Y H:i') }}
    </div>

</body>
</html>