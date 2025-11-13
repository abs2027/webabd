<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Jalan - {{ $deliveryOrder->order_number }}</title>
    
    <style>
        body {
            font-family: 'sans-serif';
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 95%;
            margin: 0 auto;
        }
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 20px;
            text-decoration: underline;
        }
        .header-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-info td {
            vertical-align: top;
            padding: 3px;
        }
        .info-label {
            width: 120px;
            font-weight: bold;
        }
        .info-separator {
            width: 10px;
        }
        
        /* Tabel Item Barang */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .items-table th, .items-table td {
            border: 1px solid #777;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f0f0f0;
            text-align: center;
        }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }
        
        /* Tanda Tangan */
        .signature-section {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-box .signature-label {
            margin-bottom: 60px; /* Ruang untuk tanda tangan */
        }
        .terms-box {
            font-size: 10px;
            color: #555;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            margin-top: 30px; /* Jarak dari tabel item */
        }
        .terms-box p {
            margin: 0 0 5px 0;
            padding: 0;
        }
    </style>
</head>
<body>

    <x-pdf.letterhead :company="$deliveryOrder->company" />

    <div class="container">
        
        <div class="document-title">SURAT JALAN</div>

        <table class="header-info">
            <tr>
                <td style="width: 50%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="info-label">No. Dokumen</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->order_number }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Penerima</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->customer_name }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Alamat Penerima</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->customer_address }}</td>
                        </tr>
                    </table>
                </td>
                
                <td style="width: 50%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="info-label">Tanggal</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->date_of_issue->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Driver</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->driver_name }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Kendaraan</td>
                            <td class="info-separator">:</td>
                            <td>{{ $deliveryOrder->vehicle_plate_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th style="width: 10%;">Kode SKU</th>
                    <th style="width: 10%;">Kuantitas</th>
                    <th style="width: 10%;">Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryOrder->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->sku }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($deliveryOrder->notes)
        <div style="margin-top: 15px;">
            <strong>Catatan:</strong>
            <p style="margin: 0;">{{ $deliveryOrder->notes }}</p>
        </div>
        @endif

        <div class="terms-box">
            <p style="font-weight: bold;">Syarat & Ketentuan:</p>
            <p>1. Dengan menandatangani Surat Jalan ini, Penerima telah memeriksa dan menyetujui bahwa barang diterima dalam kondisi baik dan jumlah yang sesuai.</p>
            
            @php
                $tenant = $deliveryOrder->company;
            @endphp

            @if($tenant->phone || $tenant->email)
            <p>2. Jika terdapat ketidak sesuaian atau keluhan setelah serah terima, paling lambat (1 x 24 jam) harap segera hubungi kami di: 

            @if($tenant->phone)
            <strong>{{ $tenant->phone }}</strong>
            @endif

            @if($tenant->phone && $tenant->email)
             / 
            @endif

            @if($tenant->email)
            <strong>{{ $tenant->email }}</strong>
            @endif

        .</p>
        <p>3. Barang yang telah diterima dan di tanda tangani tidak dapat dikembalikan atau ditukar.</p>
        <p>4. Sebagai bukti penerimaan barang yang sah, Penerima **wajib memberikan tanda tangan dan stempel (cap) perusahaan.**</p>
        @endif
        </div>
        <table class="signature-section">
            <tr>
                <td class="signature-box">
                    <div class="signature-label">Pengirim,</div>
                    <div>(.......................)</div>
                </td>
                <td style="width: 40%;"></td> <td class="signature-box">
                    <div class="signature-label">Penerima,</div>
                    <div>({{ $deliveryOrder->customer_name }})</div>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>