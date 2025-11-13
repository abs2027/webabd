<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            $quantity = $item->quantity ?? 0;
            $unit_price = $item->unit_price ?? 0;
            $item->total_price = $quantity * $unit_price;
        });

        static::updating(function (InvoiceItem $item) {
            $quantity = $item->quantity ?? 0;
            $unit_price = $item->unit_price ?? 0;
            $item->total_price = $quantity * $unit_price;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}