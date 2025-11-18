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
        'project_id',
        'parent_id',
        'name',
        'type',
        'options',
        'order',
        'operand_a',
        'operator',
        'operand_b'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

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
