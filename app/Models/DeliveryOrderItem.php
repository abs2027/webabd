<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * delivery_order_id akan diisi otomatis.
     */
    protected $fillable = [
        'product_name',
        'description',
        'sku',
        'quantity',
        'unit',
    ];

    /**
     * Relasi ke Surat Jalan (Kepala).
     * SATU Item "milik" SATU Surat Jalan.
     */
    public function deliveryOrder(): BelongsTo // <-- TAMBAHKAN FUNGSI INI
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
