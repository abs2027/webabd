<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Rekapitulasi - {{ $recap->name }}</title>
    
    <style>
        /* 1. SETTING HALAMAN */
        @page {
            margin-top: 4.5cm; 
            margin-bottom: 2cm; 
            margin-left: 1cm;
            margin-right: 1cm;
        }

        body { 
            font-family: 'sans-serif'; 
            font-size: 10px; 
            color: #333; 
        }

        /* 2. HEADER FIXED */
        header {
            position: fixed;
            top: -4.0cm; 
            left: 0cm;
            right: 0cm;
            height: 3.5cm; 
        }

        /* 3. FOOTER FIXED */
        footer {
            position: fixed; 
            bottom: -1.5cm; 
            left: 0cm; 
            right: 0cm;
            height: 1cm;
            font-size: 9px;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        .page-number:after { content: counter(page); }

        /* 4. WATERMARK */
        .watermark {
            position: fixed;
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%) rotate(-20deg);
            width: 60%; 
            z-index: -1000; 
            opacity: 0.1; 
        }
        .watermark img { width: 100%; height: auto; }

        /* STYLE LAINNYA */
        .header-title { text-align: center; font-size: 16px; font-weight: bold; margin: 0 0 20px 0; text-transform: uppercase; text-decoration: underline; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #555; padding: 5px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #eee; text-align: center; font-weight: bold; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* STYLE TANDA TANGAN (BARU) */
        .signature-wrapper {
            margin-top: 30px;
            page-break-inside: avoid; /* Mencegah tanda tangan terpotong halaman */
        }
        .signature-table {
            width: 100%;
            text-align: center;
        }
        .sign-space {
            height: 60px; /* Ruang untuk tanda tangan basah */
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    {{-- LOGIKA WATERMARK --}}
    @php
        $logoPath = null;
        $comp = $company ?? ($recap->project->company ?? null);
        if ($comp && $comp->logo_path) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($comp->logo_path)) {
                $logoPath = storage_path('app/public/' . $comp->logo_path);
            }
        }
    @endphp

    @if($logoPath)
        <div class="watermark">
            <img src="{{ $logoPath }}" alt="Watermark">
        </div>
    @endif

    <header>
        @if(isset($company))
            <x-pdf.letterhead :company="$company" />
        @endif
    </header>

    <footer>
        <table style="width: 100%;">
            <tr>
                <td style="border: none; text-align: left; width: 50%;">
                    Dicetak pada: {{ now()->format('d F Y H:i') }}
                </td>
                <td style="border: none; text-align: right; width: 50%;">
                    Halaman <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="header-title">
            Laporan Rekapitulasi: {{ $recap->name }}
        </div>

        {{-- INFORMASI HEADER (Update: Hapus Total Data) --}}
        <table style="width: 100%; margin-bottom: 20px; font-size: 10px;">
            <tr>
                <td style="width: 120px; padding-bottom: 5px; vertical-align: top;"><strong>Nama Project</strong></td>
                <td style="padding-bottom: 5px; vertical-align: top;">: {{ $recap->project->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 5px; vertical-align: top;"><strong>Klien</strong></td>
                <td style="padding-bottom: 5px; vertical-align: top;">: {{ $recap->project->client->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 5px; vertical-align: top;"><strong>Periode Data</strong></td>
                <td style="padding-bottom: 5px; vertical-align: top;">
                    : {{ $recap->start_date?->translatedFormat('d F Y') }} s/d {{ $recap->end_date?->translatedFormat('d F Y') }}
                </td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    @foreach($columns as $col)
                        <th>{{ $col->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        
                        @foreach($columns as $col)
                            @php
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
            
            <tfoot>
                <tr>
                    <td colspan="1" style="font-weight: bold; text-align: center;">TOTAL</td>
                    @foreach($columns as $col)
                        @php
                            $total = 0;
                            $hasTotal = false;
                            if ($col->is_summarized) {
                                $hasTotal = true;
                                foreach($rows as $r) {
                                    $val = $r->data;
                                    $temp = $col;
                                    $p = [];
                                    while($temp) { array_unshift($p, $temp->name); $temp = $temp->parent; }
                                    foreach($p as $k) { $val = $val[$k] ?? 0; }
                                    
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

        {{-- ▼▼▼ BAGIAN TANDA TANGAN 3 KOLOM (BARU) ▼▼▼ --}}
        <div class="signature-wrapper">
            <table class="signature-table" style="width: 100%;">
                
                {{-- BARIS 0: TANGGAL (Khusus di atas kolom kanan) --}}
                <tr>
                    <td width="33%"></td> <td width="33%"></td> <td width="33%" style="text-align: center; padding-bottom: 5px;">
                        {{-- Tanggal ditaruh di sini agar posisinya pas di atas pembuat --}}
                        {{ $company->city ?? 'Cilegon' }}, {{ now()->translatedFormat('d F Y') }}
                    </td>
                </tr>

                {{-- BARIS 1: JUDUL (Sekarang pasti sejajar karena isinya teks doang) --}}
                <tr>
                    <td width="33%" style="vertical-align: top;">
                        <div>Mengetahui,</div>
                    </td>
                    <td width="33%" style="vertical-align: top;">
                        <div>Diperiksa Oleh,</div>
                    </td>
                    <td width="33%" style="vertical-align: top;">
                        <div>Dibuat Oleh,</div>
                    </td>
                </tr>

                {{-- BARIS 2: SPASI TANDA TANGAN --}}
                <tr>
                    <td colspan="3" style="height: 60px;"></td>
                </tr>

                {{-- BARIS 3: NAMA --}}
                <tr>
                    <td width="33%" style="vertical-align: bottom;">
                        <div class="sign-name">( ........................... )</div>
                    </td>
                    <td width="33%" style="vertical-align: bottom;">
                        <div class="sign-name">( ........................... )</div>
                    </td>
                    <td width="33%" style="vertical-align: bottom;">
                        <div class="sign-name" style="font-weight: bold; text-decoration: underline;">
                            {{ auth()->user()->name ?? '( ........................... )' }}
                        </div>
                    </td>
                </tr>

            </table>
        </div>
        {{-- ▲▲▲ SELESAI TANDA TANGAN ▲▲▲ --}}

    </main>
</body>
</html>