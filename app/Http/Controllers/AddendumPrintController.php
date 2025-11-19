<?php

namespace App\Http\Controllers;

use App\Models\Addendum;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Import Library QR

class AddendumPrintController extends Controller
{
    public function __invoke(Addendum $record)
    {
        $record->load('project.company', 'project.client');
        
        // 1. Generate URL Validasi
        $validationUrl = route('validation.addendum', ['id' => $record->id]);

        // 2. Generate QR Code (Format SVG agar tajam di PDF)
        // Kita convert ke base64 agar bisa dirender oleh DomPDF
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate($validationUrl));

        // 3. Kirim ke View
        $pdf = Pdf::loadView('pdf.addendum_form', [
            'addendum' => $record,
            'project' => $record->project,
            'qrCode' => $qrCode, // Kirim variabel QR
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Form-MOM-' . $record->name . '.pdf');
    }
}