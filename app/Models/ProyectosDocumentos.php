<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectosDocumentos extends Model
{
    use HasFactory;

    protected $table = "proyectos_documentos";

    public function proyectosArray()
    {
        return $this->hasMany(Proyectos::class,'id','proyecto_id');
    }

    public function proyectos()
    {
        return $this->belongsTo(Proyectos::class,'proyecto_id','id');
    }
}
