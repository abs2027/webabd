<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recap extends Model
{
    use HasFactory;
    
    // Ini mengizinkan semua field diisi (sesuaikan jika perlu)
    protected $guarded = [];

    // ▼▼▼ INI YANG MEMPERBAIKI ERROR ANDA ▼▼▼
    // Memberi tahu bahwa satu 'Recap' (periode) MILIK SATU 'Project'
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    // ▲▲▲ PASTI BAGIAN INI YANG HILANG ▲▲▲


    // Relasi ini juga penting untuk 'RecapRowsRelationManager'
    // Memberi tahu bahwa satu 'Recap' (periode) PUNYA BANYAK 'RecapRow' (data)
    public function recapRows(): HasMany
    {
        return $this->hasMany(RecapRow::class);
    }
}