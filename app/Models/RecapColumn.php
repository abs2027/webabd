<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecapColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'recap_type_id', // <-- DIGANTI: Dulu project_id
        'parent_id',
        'name',
        'type',
        'options',
        'order',
        'operand_a',
        'operator',
        'operand_b',
        'is_summarized'
    ];

    /**
     * Sekarang menginduk ke RecapType (Jenis Rekap), bukan langsung ke Project.
     */
    public function recapType(): BelongsTo
    {
        return $this->belongsTo(RecapType::class);
    }

    /**
     * Helper Opsional:
     * Jika di kodemu nanti ada yang memanggil $column->project,
     * fungsi ini akan mencarikan project-nya lewat jalur RecapType.
     * Jadi kodingan lama tidak error.
     */
    public function getProjectAttribute()
    {
        return $this->recapType->project;
    }

    // --- BAGIAN DI BAWAH INI TETAP SAMA ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RecapColumn::class, 'parent_id');
    }

    /**
     * Mendapatkan semua turunan (children) dari kolom ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(RecapColumn::class, 'parent_id');
    }
}