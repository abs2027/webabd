<?php

namespace App\Http\Controllers;

use App\Models\Addendum;
use Illuminate\Http\Request;

class DocumentValidationController extends Controller
{
    public function checkAddendum($id)
    {
        // Cari dokumen berdasarkan ID (atau hash unik biar lebih aman)
        $addendum = Addendum::with('project.company', 'project.client')->findOrFail($id);

        // Tampilkan halaman validasi
        return view('validation.addendum', compact('addendum'));
    }
}