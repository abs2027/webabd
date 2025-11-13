<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Void_;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'logo_path', // <-- TAMBAHKAN INI
        'address',   // <-- TAMBAHKAN INI
        'phone',     // <-- TAMBAHKAN INI
        'email',     // <-- TAMBAHKAN INI
    ];

    /**
     * Otomatis membuat slug saat membuat Company baru.
     */
    protected static function booted(): void // <-- TAMBAHKAN SEMUA METHOD INI
    {
        static::creating(function (Company $company) {
            $company->slug = Str::slug($company->name);
        });
    }

    /**
     * Beritahu Laravel untuk menggunakan 'slug' di URL, bukan 'id'.
     */
    public function getRouteKeyName(): string // <-- TAMBAHKAN SEMUA METHOD INI
    {
        return 'slug';
    }

    /**
     * Relasi Company ke User.
     */
    public function users(): BelongsToMany // <-- TAMBAHKAN METHOD INI
    {
        return $this->belongsToMany(User::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    /**
     * Relasi ke Invoice (Baru).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
