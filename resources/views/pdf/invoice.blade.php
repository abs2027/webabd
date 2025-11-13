<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    
    <style>
        body { font-family: 'sans-serif'; font-size: 11px; color: #333; }
        .container { width: 95%; margin: 0 auto; }
        .document-title { text-align: center; font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 20px; text-decoration: underline; text-transform: uppercase; color: #333; }
        .header-info { width: 100%; margin-bottom: 20px; font-size: 10px; }
        .header-info td { vertical-align: top; padding: 2px 5px; }
        .info-label { font-weight: bold; width: 60px; }
        .customer-block { }
        .customer-label { font-size: 10px; color: #555; margin-bottom: 3px; }
        .customer-name { font-weight: bold; font-size: 11px; color: #000; }
        .customer-address { font-size: 10px; line-height: 1.5; margin-top: 4px; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        
        .items-table th, .items-table td { 
            border: 1px solid #777; 
            padding: 6px; 
            text-align: left; 
            vertical-align: middle; /* Rata tengah vertikal untuk isi tabel */
        }
        
        .items-table th { background-color: #f0f0f0; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .summary-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        
        .terbilang-cell { 
            width: 60%; 
            vertical-align: middle; 
            padding: 10px; 
            border: 1px solid #777; 
            font-style: italic; 
            font-weight: bold;
            font-size: 12px;
        }

        .summary-table td.total-label,
        .summary-table td.currency,
        .summary-table td.total-value {
            border: 1px solid #777;
            padding: 6px 10px;
            font-size: 11px;
            vertical-align: top; 
        }
        
        .total-label { 
            font-weight: bold; 
            text-align: right; 
            width: 20%;        
        }
        
        .currency { 
            width: 5%;        
            text-align: left; 
        } 
        
        .total-value { 
            text-align: right; 
            width: 15%;       
        }
        
        /* ▼▼▼ CSS BARU UNTUK T&C ▼▼▼ */
        .terms-box {
            font-size: 9px; /* Font lebih kecil */
            color: #555;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            margin-top: 20px; /* Jarak dari total */
        }
        .terms-box p {
            margin: 0 0 5px 0;
            padding: 0;
        }
        /* ▲▲▲ BATAS CSS BARU ▲▲▲ */
        
        .footer-info { margin-top: 15px; font-size: 10px; }
        .signature-section { width: 30%; text-align: center; float: right; margin-top: 20px; }
        .signature-label { margin-bottom: 50px; }
    </style>
</head>
<body>

    <!-- 1. KOP SURAT (Reusable Component) -->
    <x-pdf.letterhead :company="$invoice->company" />

    <div class="container">
        
        <!-- 2. JUDUL DOKUMEN (Gaya Surat Jalan) -->
        <div class="document-title">Invoice</div>

        <!-- 3. DETAIL KEPALA (Header) -->
        <table class="header-info">
            <!-- ... (Kode header Anda, tidak berubah) ... -->
            <tr>
                <td style="width: 55%;" class="customer-block">
                    <div class="customer-label">Kepada Yth.</div>
                    <div class="customer-name">{{ $invoice->customer_name }}</div>
                    @if($invoice->customer_address)
                    <div class="customer-address">
                        {!! nl2br(e($invoice->customer_address)) !!}
                    </div>
                    @endif
                </td>
                <td style="width: 45%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="info-label">Tanggal</td>
                            <td>: {{ $invoice->invoice_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">No. Invoice</td>
                            <td>: {{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">No. PO</td>
                            <td>: {{ $invoice->po_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- 4. TABEL ITEM BARANG (Header Ringkas) -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 10%;">KODE</th>
                    <th style="width: 35%;">DESKRIPSI</th> <!-- Lebar disesuaikan -->
                    <th style="width: 5%;" class="text-right">QTY</th>
                    <th style="width: 5%;" class="text-center">UNIT</th> <!-- KOLOM BARU -->
                    <th style="width: 15%;" class="text-right">HARGA (IDR)</th>
                    <th style="width: 15%;" class="text-right">TOTAL (IDR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->product_code }}</td>
                    <td class="text-left">{!! nl2br(e($item->description)) !!}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- 5. TOTAL & TERBILANG -->
        <table class="summary-table">
            <!-- ... (Kode tabel total Anda, tidak berubah) ... -->
            <tr>
                <td class="terbilang-cell" rowspan="4">
                    @php
                        Config::set('terbilang.locale', 'id');
                        $terbilangText = \Terbilang::make($invoice->total_amount, ' rupiah');
                    @endphp 
                    "{{ ucfirst($terbilangText) }}"
                </td>
                <td class="total-label">SUB TOTAL</td>
                <td class="currency">Rp</td>
                <td class="total-value text-right">{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label">DPP</td>
                <td class="currency">Rp</td>
                <td class="total-value text-right">{{ number_format($invoice->subtotal * (11/12), 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label">PPN ({{ number_format($invoice->tax_rate, 0) }}%)</td>
                <td class="currency">Rp</td>
                <td class="total-value text-right">{{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label font-bold">TOTAL</td>
                <td class="currency font-bold">Rp</td>
                <td class="total-value text-right font-bold">{{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
            </tr>
        </table>
        
        <!-- ▼▼▼ BLOK T&C BARU DITAMBAHKAN DI SINI ▼▼▼ -->
        <div class="terms-box">
            <p style="font-weight: bold;">Syarat & Ketentuan:</p>
            <p>1. Harap lakukan pembayaran sesuai dengan total tagihan ke rekening bank yang tertera.</p>
            
            @php
                $tenant = $invoice->company;
            @endphp

            @if($tenant->phone || $tenant->email)
            <p>2. Jika terdapat ketidaksesuaian tagihan, harap segera hubungi kami di: 
                @if($tenant->phone) <strong>{{ $tenant->phone }}</strong> @endif
                @if($tenant->phone && $tenant->email) / @endif
                @if($tenant->email) <strong>{{ $tenant->email }}</strong> @endif
            .</p>
            @endif
            
            <p>3. Invoice ini sah dan diakui sebagai bukti tagihan yang valid.</p>
        </div>
        <!-- ▲▲▲ BATAS BLOK T&C BARU ▲▲▲ -->

        <!-- 6. INFO BANK & TANDA TANGAN -->
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