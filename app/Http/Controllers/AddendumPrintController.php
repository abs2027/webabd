<?php

namespace App\Http\Controllers;

use App\Models\Addendum;
use Barryvdh\DomPDF\Facade\Pdf;

class AddendumPrintController extends Controller
{
    public function __invoke(Addendum $record)
    {
        // Load relasi company untuk kop surat
        $record->load('project.company', 'project.client');
        
        $pdf = Pdf::loadView('pdf.addendum_form', [
            'addendum' => $record,
            'project' => $record->project,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Form-Adendum-' . $record->name . '.pdf');
    }
}