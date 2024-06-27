<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvocatoriasEURawData extends Model
{
    use HasFactory;

    protected $table = "convocatorias_europeas_raw_data";

    function convocatoria_innovating(){
        return $this->belongsTo(\App\Models\Ayudas::class, 'id', 'id_raw_data');      
    }
}
