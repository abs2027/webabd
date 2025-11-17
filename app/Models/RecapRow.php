<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecapRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'data',
    ];

    /**
     * Otomatis ubah JSON 'data' menjadi array PHP
     */
    protected $casts = [
        'data' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}