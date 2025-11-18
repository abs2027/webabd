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
        // 1. Ambil Data
        $recap = $record;
        $project = $recap->project;

        // 2. Ambil Struktur Kolom (Sama seperti logic di RelationManager)
        // Kita butuh tahu kolom mana saja yang harus ditampilkan
        $columns = $project->recapColumns()
            ->where('type', '!=', 'group')
            ->orderBy('order')
            ->get();

        // 3. Ambil Baris Data
        $rows = $recap->recapRows()->get();

        // 4. Generate PDF
        // Kita load view 'pdf.recap_report' (yang sudah kita buat sebelumnya)
        $pdf = Pdf::loadView('pdf.recap_report', [
            'recap' => $recap,
            'columns' => $columns,
            'rows' => $rows,
            'company' => $project->company ?? null, // Asumsi ada relasi ke company
        ])->setPaper('a4', 'landscape'); // Landscape agar muat banyak kolom

        // 5. STREAM PDF (Bukan Download)
        // Ini kuncinya agar tampil di browser
        return $pdf->stream('Laporan-Rekap-' . Str::slug($recap->name) . '.pdf');
    }
}