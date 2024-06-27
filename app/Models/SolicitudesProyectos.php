<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudesProyectos extends Model
{
    use HasFactory;

    protected $table = "solicitudes_proyectos";

    public function participante(){
        return $this->belongsTo(\App\Models\Entidad::class,'cifParticipante','CIF');
    }

    public function proyecto(){
        return $this->belongsTo(\App\Models\Proyectos::class,'IdProyecto','id');
    }

    public function organizador(){
        return $this->belongsTo(\App\Models\Entidad::class,'cifPrincipal','CIF');
    }

    public function busqueda(){
        return $this->belongsTo(\App\Models\Encaje::class,'IdEncaje','id');
    }

}
