<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concessions extends Model
{
    use HasFactory;

    function organo(){
        return $this->belongsTo(Organos::class, 'id_organo', 'id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class, 'id_departamento', 'id');
    }

    function entidad(){
        return $this->belongsTo(Entidad::class, 'custom_field_cif', 'CIF');
    }
}
