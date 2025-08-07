<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crips extends Model
{
    use HasFactory;
    protected $table = "crips";
    protected $guarded = [];
    public function kriteria()
{
    return $this->belongsTo(Kriteria::class, 'kriteria_id');
}
}
