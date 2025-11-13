@props([
    'company' => null, // Menerima data 'company' (tenant)
])

@php
    // Jika data company tidak diberikan, ambil dari tenant yang sedang aktif
    $tenant = $company ?? filament()->getTenant();
    $logoUrl = $tenant->logo_path ? storage_path('app/public/' . $tenant->logo_path) : null;
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
        padding-bottom: 5px; /* <-- TAMBAHKAN INI (5px untuk jarak "dikit") */
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
        margin-bottom: 5px;
    }
    .company-address, .company-contact {
        font-size: 11px;
        color: #555;
        margin: 2px 0;
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