<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kwitansi - {{ $invoice->invoice_number }}</title>
    
    <style>
        body { font-family: 'sans-serif'; font-size: 11px; color: #333; }
        .container { width: 95%; margin: 0 auto; }
        
        /* Judul Dokumen */
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: #333;
        }
        .receipt-number {
            text-align: center;
            font-size: 11px;
            margin-bottom: 30px;
        }
        
        /* Tabel utama Kwitansi */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .receipt-table td {
            padding: 8px 0;
            vertical-align: top;
        }
        .receipt-label {
            width: 150px; /* Lebar label "Telah terima dari" */
            font-weight: bold;
        }
        .receipt-separator {
            width: 15px;
            text-align: center;
        }
        
        /* Kotak Terbilang (sesuai foto) */
        .terbilang-box {
            border: 1px solid #777;
            background-color: #f9f9f9; /* <-- Background abu-abu */
            padding: 10px 15px;
            font-style: italic;
            font-weight: bold;
            font-size: 13px;
        }
        
        /* Kotak Jumlah (sesuai foto) */
        .amount-box {
            border: 1px solid #777;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 13px;
            background-color: #f9f9f9;
            width: 250px; /* Lebar kotak "Jumlah" */
        }
        .amount-box .currency {
            margin-right: 15px;
        }

        /* ▼▼▼ CSS BARU UNTUK GARIS BAWAH ▼▼▼ */
        .fill-line {
            border-bottom: 1px solid #777; /* Garis bawah */
            padding-bottom: 2px;
            width: 100%;
        }
        .receipt-data-inline {
            font-weight: bold;
            font-size: 13px;
            padding-left: 5px; /* Jarak sedikit dari titik dua */
        }
        /* ▲▲▲ BATAS CSS BARU ▲▲▲ */
        
        /* Info Bank & TTD */
        .footer-info { margin-top: 25px; font-size: 10px; }
        .signature-section { width: 30%; text-align: center; float: right; margin-top: 20px; }
        .signature-label { margin-bottom: 50px; }
    </style>
</head>
<body>

    <!-- 1. KOP SURAT (Reusable Component) -->
    <x-pdf.letterhead :company="$invoice->company" />

    <div class="container">
        
        <!-- 2. JUDUL DOKUMEN -->
        <div class="document-title">Kwitansi</div>
        <div class="receipt-number">
            No. : {{ $invoice->invoice_number }}
        </div>

        <!-- 3. DETAIL KWITANSI (DENGAN GARIS BAWAH) -->
        <table class="receipt-table">
            <tr>
                <td class="receipt-label">Telah terima dari</td>
                <td class="receipt-separator">:</td>
                <td class="fill-line"> <!-- <-- DIBERI GARIS BAWAH -->
                    <span class="receipt-data-inline">{{ $invoice->customer_name }}</span>
                </td>
            </tr>
            <tr>
                <td class="receipt-label">Uang sejumlah</td>
                <td class="receipt-separator">:</td>
                <td class="terbilang-box">
                    @php
                        // Kode terbilang Anda yang sudah benar
                        Config::set('terbilang.locale', 'id');
                        $terbilangText = \Terbilang::make($invoice->total_amount, ' rupiah');
                    @endphp 
                    "{{ ucfirst($terbilangText) }}"
                </td>
            </tr>
            <tr>
                <td class="receipt-label">Untuk Pembayaran</td>
                <td class="receipt-separator">:</td>
                <td class="fill-line"> <!-- <-- DIBERI GARIS BAWAH -->
                    <span class="receipt-data-inline">
                        Pembayaran Invoice No. {{ $invoice->invoice_number }}
                        @if($invoice->items->count() > 0)
                            untuk {{ $invoice->items->first()->description }}
                            @if($invoice->items->count() > 1)
                                dan {{ $invoice->items->count() - 1 }} lainnya.
                            @endif
                        @endif
                    </span>
                </td>
            </tr>
            <tr>
                <td class="receipt-label" style="padding-top: 15px;">Jumlah</td>
                <td class="receipt-separator" style="padding-top: 15px;">:</td>
                <td style="padding-top: 15px;">
                    <span class="amount-box">
                        <span class="currency">Rp</span>
                        {{ number_format($invoice->total_amount, 2, ',', '.') }}
                    </span>
                </td>
            </tr>
        </table>
        
        <!-- 4. INFO BANK & TANDA TANGAN -->
        <div class="footer-info">
            {!! nl2br(e($invoice->bank_details)) !!}
        </div>
        
        <div class="signature-section">
            <div class="signature-label">
                Cilegon, {{ $invoice->invoice_date->format('d F Y') }}<br>
                PT. {{ $invoice->company->name }}
            </div>
            <div>
                Direktur
            </div>
        </div>

    </div>

</body>
</html>