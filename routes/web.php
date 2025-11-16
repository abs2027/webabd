<?php

use Illuminate\Support\Facades\Route;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\IdentifyTenant;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/{tenant}/delivery-orders/{record}/print', function ($tenant, DeliveryOrder $record) {
    $tenant = filament()->getTenant();

    // Jika tenant tidak terdeteksi, tolak akses
    if (! $tenant) {
        abort(403, 'Tenant tidak ditemukan atau belum diidentifikasi.');
    }

    // Cek apakah record sesuai dengan tenant aktif
    if ($record->company_id !== $tenant->id) {
        abort(403, 'Anda tidak memiliki akses ke data ini.');
    }

    $pdf = Pdf::loadView('pdf.delivery_order', [
        'deliveryOrder' => $record
    ]);

    // 1. Kita 'bersihkan' nomor order dari karakter '/' untuk nama file
    $safeOrderNumber = str_replace('/', '-', $record->order_number);

    // 2. Gunakan nama file yang sudah aman
    return $pdf->stream('SuratJalan-' . $safeOrderNumber . '.pdf');
})
->name('print.delivery-order')
->middleware([
    Authenticate::class . ':admin',
    IdentifyTenant::class . ':admin',
]);

/*
|--------------------------------------------------------------------------
| Rute Cetak PDF Invoice
|--------------------------------------------------------------------------
*/
Route::get('/{tenant}/invoices/{record}/print-invoice', function ($tenant, Invoice $record) {

    // Otorisasi
    if ($record->company_id !== filament()->getTenant()->id) {
        abort(403, 'Anda tidak memiliki akses ke data ini.');
    }

    // Load PDF
    $pdf = Pdf::loadView('pdf.invoice', [
        'invoice' => $record // Kirim data invoice ke 'cetakan'
    ]);

    // Kirim ke Browser
    $safeInvoiceNumber = str_replace('/', '-', $record->invoice_number);
    return $pdf->stream('Invoice-' . $safeInvoiceNumber . '.pdf');

})->name('print.invoice') // <-- Nama rute baru
->middleware([
    Authenticate::class . ':admin',
    IdentifyTenant::class . ':admin',
]);

Route::get('/{tenant}/invoices/{record}/print-receipt', function ($tenant, Invoice $record) {
    
    // Otorisasi
    if ($record->company_id !== filament()->getTenant()->id) {
        abort(403, 'Anda tidak memiliki akses ke data ini.');
    }

    // Load PDF
    $pdf = Pdf::loadView('pdf.receipt', [
        'invoice' => $record // Kirim data invoice ke 'cetakan' kwitansi
    ]);

    // Kirim ke Browser
    $safeInvoiceNumber = str_replace('/', '-', $record->invoice_number);
    return $pdf->stream('Kwitansi-' . $safeInvoiceNumber . '.pdf');

})->name('print.receipt') // <-- Nama rute baru
->middleware([
    Authenticate::class . ':admin',
    IdentifyTenant::class . ':admin',
]);