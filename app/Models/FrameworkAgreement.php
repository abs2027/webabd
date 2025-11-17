<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrameworkAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 
        'fa_number', 
        'fa_date', 
        'fa_document_path'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}