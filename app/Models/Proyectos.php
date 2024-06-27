<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyectos extends Model
{
    use HasFactory;

    protected $table = 'proyectos';

    protected $guarded = [];

    public function maincompany(){
        return $this->belongsTo(Entidad::class, 'empresaPrincipal', 'CIF');
    }

    public function cifnozoho(){
        return $this->belongsTo(CifsNoZoho::class, 'empresaPrincipal', 'CIF');
    }

    function organo(){
        return $this->belongsTo(Organos::class, 'organismo', 'id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class, 'organismo', 'id');
    }

    function participantes(){
        return $this->hasMany(Participantes::class, 'id_proyecto', 'id');
    }

    function ayudaAcronimo(){
        return $this->belongsTo(Ayudas::class, 'idAyudaAcronimo', 'IdConvocatoriaStr');
    }

    function convocatoriaAcronimo(){
        return $this->belongsTo(Convocatorias::class, 'idConvocatoriaAcronimo', 'id');
    }

    function concesion(){
        return $this->belongsTo(Concessions::class, 'idConcesion', 'id');
    }

    function concesion_data_elastic(){
        return $this->belongsTo(Concessions::class, 'id_europeo', 'id');
    }

    function encajes(){
        return $this->hasMany(Encaje::class, 'Proyecto_id', 'id');
    }

    function ayuda(){
        return $this->belongsTo(Ayudas::class, 'IdAyuda', 'id');
    }

    function lastSolicitud(){
        return $this->hasMany(SolicitudesProyectos::class, 'IdProyecto', 'id')->latest();
    }

    function analisis(){
        return $this->belongsTo(AnalisisProyectos::class, 'id_analisis', 'id');
    }

    function documentosPublicos(){
        return $this->hasMany(ProyectosDocumentos::class, 'proyecto_id', 'id')->where('visibilidad', 1);
    }

    function documentosPrivados(){
        return $this->hasMany(ProyectosDocumentos::class, 'proyecto_id', 'id')->where('visibilidad', 0);
    }

    function keywords(){
        return $this->belongsTo(ChatGPTProyectosKeywords::class, 'id', 'id_proyecto');
    }
    
}
