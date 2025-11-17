<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'payment_term_value', // <-- TAMBAHKAN INI
        'payment_term_unit',
    ];

    // Project ini milik satu Company (Tenant)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Project ini milik satu Client
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

    public function recapColumns(): HasMany
    {
        return $this->hasMany(RecapColumn::class)->orderBy('order');
    }

    public function recapRows(): HasMany
    {
        return $this->hasMany(RecapRow::class);
    }
}