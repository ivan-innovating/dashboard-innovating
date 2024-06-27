<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noticias extends Model
{
    use HasFactory;

    protected $table = 'noticias';

    function organo(){
        return $this->belongsTo(Organos::class,'id_organo','id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class,'id_organo','id');
    }

    function convocatoria(){
        return $this->belongsTo(Ayudas::class,'id_ayuda','id');
    }
}
