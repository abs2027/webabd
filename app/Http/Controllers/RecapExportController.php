<?php

namespace App\Http\Controllers;

use App\Models\Recap;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class RecapExportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Recap $record)
    {
        // 1. Ambil Data Rekap
        $recap = $record;
        
        // Load relasi 'recapType' agar kita bisa ambil kolomnya
        $recap->load('recapType');

        // Ambil Project (sekarang lewat jalur RecapType)
        // Pastikan model RecapType punya relasi public function project()
        $project = $recap->recapType->project; 

        // 2. Ambil Struktur Kolom (DIPERBAIKI)
        // DULU: $project->recapColumns() -> Error karena sudah dihapus
        // SEKARANG: Ambil dari $recap->recapType->recapColumns()
        $columns = $recap->recapType->recapColumns()
            ->where('type', '!=', 'group')
            ->orderBy('order')
            ->get();

        // 3. Ambil Baris Data
        $rows = $recap->recapRows()->get();

        // 4. Generate PDF
        // Menggunakan view 'pdf.recap_report' sesuai kode lama Anda
        $pdf = Pdf::loadView('pdf.recap_report', [
            'recap' => $recap,
            'columns' => $columns,
            'rows' => $rows,
            'project' => $project, // Kirim variable project untuk Kop Surat
            'company' => $project->company ?? null, 
        ])->setPaper('a4', 'landscape'); 

        // 5. STREAM PDF
        return $pdf->stream('Laporan-Rekap-' . Str::slug($recap->name) . '.pdf');
    }
}