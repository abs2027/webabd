<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * company_id akan diisi otomatis oleh Filament.
     */
    protected $fillable = [
        'order_number',
        'date_of_issue',
        'customer_name',
        'customer_address',
        'driver_name',
        'vehicle_plate_number',
        'notes',
    ];

    /**
     * Atribut yang harus di-cast.
     */
    protected $casts = [
        'date_of_issue' => 'date',
    ];

    /**
     * Relasi ke Company (Tenant).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi ke Item-item Barang (Baru).
     * SATU Surat Jalan memiliki BANYAK Item.
     */
    public function items(): HasMany // <-- TAMBAHKAN FUNGSI INI
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }
}
