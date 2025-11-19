<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recap extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // ==========================================================
    // ▼▼▼ PERUBAHAN PENTING DI SINI ▼▼▼
    // ==========================================================

    /**
     * HUBUNGAN BARU:
     * Periode Rekap sekarang milik "Jenis Rekap" (RecapType).
     * Contoh: Periode Oktober milik "Rekap Solar".
     */
    public function recapType(): BelongsTo
    {
        return $this->belongsTo(RecapType::class);
    }

    /**
     * JEMBATAN (HELPER):
     * Karena kolom 'project_id' sudah dihapus, fungsi project() yg lama akan error.
     * Kita ganti dengan "Accessor" pintar.
     * * Jadi kalau kamu panggil $recap->project, dia akan otomatis
     * mencari Project lewat jalur RecapType.
     */
    public function getProjectAttribute()
    {
        // Jika punya tipe rekap, ambil project dari tipe tersebut
        return $this->recapType ? $this->recapType->project : null;
    }

    // ==========================================================
    // ▲▲▲ SELESAI PERUBAHAN ▲▲▲
    // ==========================================================

    public function recapRows(): HasMany
    {
        return $this->hasMany(RecapRow::class);
    }
}