<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // Tambahan untuk jalan pintas

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'payment_term_value',
        'payment_term_unit',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function frameworkAgreements(): HasMany
    {
        return $this->hasMany(FrameworkAgreement::class);
    }

    // ============================================================
    // ▼▼▼ PERUBAHAN DI SINI ▼▼▼
    // ============================================================

    /**
     * 1. RELASI UTAMA BARU
     * Project memiliki banyak Jenis Rekap (Map dalam Lemari).
     */
    public function recapTypes(): HasMany
    {
        return $this->hasMany(RecapType::class);
    }

    /**
     * 2. JALAN PINTAS (Opsional tapi Berguna)
     * Jika kamu ingin mengambil SEMUA data rekap dari semua jenis
     * (misal: untuk menghitung total omzet proyek secara global).
     * Kita pakai 'HasManyThrough' (Punya banyak melalui...).
     */
    public function allRecaps(): HasManyThrough
    {
        return $this->hasManyThrough(Recap::class, RecapType::class);
    }

    // ============================================================
    // ⚠️ HUBUNGAN LAMA (NONAKTIFKAN)
    // Kita matikan supaya kodingan lama tidak salah ambil jalur.
    // ============================================================

    // public function recaps(): HasMany
    // {
    //     return $this->hasMany(Recap::class);
    // }

    // public function recapColumns(): HasMany
    // {
    //     return $this->hasMany(RecapColumn::class)->orderBy('order');
    // }
}