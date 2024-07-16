<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglasScrappers extends Model
{
    use HasFactory;

    protected $table = "reglas_scrappers";

    function convocatoria(){
        return $this->belongsTo(Ayudas::class, 'id_convocatoria', 'id');
    }
}
