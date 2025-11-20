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
        'recap_type_id',
        'parent_id',
        'name',
        'type',
        'options',
        'order',
        'operand_a',
        'operator',
        'operand_b',
        'is_summarized',
        // 'is_duplicate_check', <--- SUDAH DIHAPUS
    ];

    protected $casts = [
        'is_summarized' => 'boolean',
        // 'is_duplicate_check' => 'boolean', <--- SUDAH DIHAPUS
        'options' => 'array',
    ];

    public function recapType(): BelongsTo
    {
        return $this->belongsTo(RecapType::class);
    }

    public function getProjectAttribute()
    {
        return $this->recapType->project;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RecapColumn::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RecapColumn::class, 'parent_id');
    }
}