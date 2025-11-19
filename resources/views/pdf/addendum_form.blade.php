<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Berita Acara - {{ $addendum->name }}</title>
    <style>
        /* 1. MARGIN HALAMAN */
        @page { margin: 1cm 2cm; }
        
        body { font-family: sans-serif; font-size: 9pt; color: #333; position: relative; } 
        
        /* 2. WATERMARK BACKGROUND */
        .watermark {
            position: fixed;
            top: 35%;
            left: 25%; 
            width: 50%;
            z-index: -1000; 
            opacity: 0.1; 
            transform: rotate(-30deg); 
        }
        .watermark img {
            width: 100%;
            height: auto;
        }

        /* UPDATE POSISI QR CODE: GUNAKAN NILAI NEGATIF */
        .qr-code-container {
            position: fixed;
            /* Masukkan ke area margin bawah (karena margin bawah 1cm/~37px) */
            bottom: -10px;    
            /* Geser jauh ke kanan masuk ke margin kanan (karena margin kanan 2cm/~75px) */
            right: -65px;      
            text-align: center;
            z-index: 100;
        }
        .qr-code-img {
            width: 50px;  /* Diperkecil sedikit lagi biar rapi */
            height: 50px;
        }
        .qr-label {
            font-size: 5pt;
            margin-top: 2px;
            color: #555;
            font-weight: bold;
        }

        /* STYLE LAINNYA TETAP SAMA */
        .title-container { text-align: center; margin-top: 0px; margin-bottom: 15px; }
        .title { font-size: 14pt; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
        .subtitle { font-size: 10pt; margin-top: 5px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9pt; }
        .info-table td { padding: 2px; vertical-align: top; }

        .handwriting-area {
            border: 1px solid #000;
            padding: 10px;
            min-height: 200px; 
            margin-bottom: 10px;
            background-color: transparent; 
        }
        .dotted-line {
            border-bottom: 1px dotted #999;
            height: 20px; 
            width: 100%;
            margin-bottom: 5px;
        }
        .instruction { color: #777; font-style: italic; font-size: 8pt; margin-bottom: 5px; text-align: center; }

        .signature-section {
            page-break-inside: avoid; 
            margin-top: 10px;
        }
        .signature-header {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            font-size: 9pt;
        }
        .signature-table { width: 100%; margin-bottom: 15px; } 
        .signature-table td { 
            width: 33.33%; 
            text-align: center; 
            vertical-align: top; 
            padding: 0 5px;
        }
        .sign-space { height: 50px; }
        .sign-name { 
            border-top: 1px solid #333; 
            display: inline-block; 
            width: 95%; 
            padding-top: 2px; 
            font-weight: normal;
            color: #777;
            font-size: 8pt;
        }
    </style>
</head>
<body>

    <!-- 3. LOGO WATERMARK -->
    @php
        $logoPath = null;
        if ($project->company && $project->company->logo_path) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($project->company->logo_path)) {
                $logoPath = storage_path('app/public/' . $project->company->logo_path);
            }
        }
    @endphp

    @if($logoPath)
        <div class="watermark">
            <img src="{{ $logoPath }}" alt="Watermark">
        </div>
    @endif

    <!-- QR CODE VALIDASI -->
    @if(isset($qrCode))
    <div class="qr-code-container">
        <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-code-img" alt="QR Validation">
        <div class="qr-label">Scan Validasi</div>
    </div>
    @endif

    <!-- KOP SURAT -->
    @if($project->company)
        <x-pdf.letterhead :company="$project->company" />
    @endif

    <!-- JUDUL -->
    <div class="title-container">
        <div class="title">BERITA ACARA / MINUTES OF MEETING</div>
        <div class="subtitle">Nomor: .................................................</div>
    </div>

    <!-- INFO PROYEK -->
    <table class="info-table">
        <tr>
            <td width="15%"><strong>Proyek</strong></td>
            <td width="2%">:</td>
            <td>{{ $project->name }}</td>
        </tr>
        <tr>
            <td><strong>Klien</strong></td>
            <td>:</td>
            <td>{{ $project->client->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Perihal</strong></td>
            <td>:</td>
            <td><strong>{{ $addendum->name }}</strong></td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td>{{ $addendum->date->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <p style="margin-bottom: 5px; font-size: 9pt;">Pada hari ini, tanggal <strong>{{ $addendum->date->translatedFormat('d F Y') }}</strong>, telah disepakati hasil pertemuan/klarifikasi dengan rincian:</p>

    <!-- AREA TULIS TANGAN -->
    <div class="handwriting-area">
        <div class="instruction">(Tulis rincian hasil meeting di sini)</div>
        @for($i=0; $i<8; $i++)
            <div class="dotted-line"></div>
        @endfor
    </div>

    <!-- TANDA TANGAN -->
    
    <!-- BARIS 1: PIHAK KLIEN -->
    <div class="signature-section">
        <div class="signature-header">
            PIHAK KLIEN : {{ $project->client->name ?? '' }}
        </div>
        <table class="signature-table">
            <tr>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
            </tr>
        </table>
    </div>

    <!-- BARIS 2: PIHAK KONTRAKTOR -->
    <div class="signature-section">
        <div class="signature-header">
            PIHAK KONTRAKTOR : {{ $project->company->name ?? '' }}
        </div>
        <table class="signature-table">
            <tr>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
                <td><div class="sign-space"></div><div class="sign-name">( Nama Jelas )</div></td>
            </tr>
        </table>
    </div>

</body>
</html>