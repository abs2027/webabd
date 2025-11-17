@props([
    'company' => null, // Menerima data 'company' (tenant)
])

@php
    // Jika data company tidak diberikan, ambil dari tenant yang sedang aktif
    $tenant = $company ?? filament()->getTenant();
    
    // Perbaikan: Cek apakah logo_path ada dan file-nya ada di storage
    // dompdf memerlukan path absolut, bukan URL
    $logoUrl = null;
    if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
        $logoUrl = storage_path('app/public/' . $tenant->logo_path);
    }
@endphp

<style>
    /* CSS Sederhana untuk Kop Surat - dompdf suka yang simpel */
    .letterhead-table {
        width: 100%;
        border-bottom: 2px solid #000; /* Garis bawah hitam tebal */
        padding-bottom: 10px;
        border-collapse: collapse;
    }
    .logo-container {
        width: 100px; /* Lebar untuk logo */
        vertical-align: top;
        padding-bottom: 10px; /* Beri jarak bawah agar logo tidak menempel garis */
    }
    .logo {
        max-width: 90px;
        max-height: 90px;
    }
    .company-details {
        vertical-align: top;
        text-align: left;
        padding-left: 15px;
    }
    .company-name {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin-bottom: 1px; /* <-- DIKURANGI (biar mepet) */
    }
    /* =========================================== */
    /* STYLE BARU ANDA (DISESUAIKAN) */
    /* =========================================== */
    .company-tagline {
        font-size: 11px;
        /* font-style: italic; */ /* <-- DIHAPUS (tidak miring) */
        color: #555; /* <-- Diganti warnanya agar seragam */
        margin: 0 0 8px 0; /* Jarak atas 0 (mepet), bawah 8px (ke alamat) */
        line-height: 1.4;
    }
    .company-address, .company-contact {
        font-size: 11px;
        color: #555;
        margin: 0; /* <-- DIBUAT 0 (agar mepet jadi satu blok) */
        line-height: 1.4;
    }
</style>

<table class="letterhead-table">
    <tr>
        @if($logoUrl)
        <td class="logo-container">
            <img src="{{ $logoUrl }}" alt="Logo" class="logo">
        </td>
        @endif
        
        <td class="company-details">
            <div class="company-name">{{ $tenant->name }}</div>
            
            <!-- =========================================== -->
            <!--             DATA BARU ANDA MUNCUL DI SINI     -->
            <!-- =========================================== -->
            @if($tenant->business_description)
            <div class="company-tagline">
                {{ $tenant->business_description }}
            </div>
            @endif
            
            @if($tenant->address)
            <div class="company-address">
                {{ $tenant->address }}
            </div>
            @endif
            
            @if($tenant->phone || $tenant->email)
            <div class="company-contact">
                @if($tenant->phone)
                <span>Telp: {{ $tenant->phone }}</span>
                @endif
                
                @if($tenant->phone && $tenant->email)
                <span> | </span>
                @endif

                @if($tenant->email)
                <span>Email: {{ $tenant->email }}</span>
                @endif
            </div>
            @endif
        </td>
    </tr>
</table>