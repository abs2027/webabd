<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 
        'po_number', 
        'po_date', 
        'po_document_path'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
