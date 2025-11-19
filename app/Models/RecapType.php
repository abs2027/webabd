<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecapType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Anak-anak pindah ke sini
    public function recapColumns()
    {
        return $this->hasMany(RecapColumn::class)->orderBy('order');
    }

    public function recaps()
    {
        return $this->hasMany(Recap::class);
    }
}
