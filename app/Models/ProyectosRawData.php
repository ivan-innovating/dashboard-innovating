<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectosRawData extends Model
{
    use HasFactory;

    protected $table = "proyectos_rawdata";

    function organo(){
        return $this->belongsTo(Organos::class, 'id_organismo', 'id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class, 'id_organismo', 'id');
    }
}
