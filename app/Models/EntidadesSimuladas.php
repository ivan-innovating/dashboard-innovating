<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadesSimuladas extends Model
{
    use HasFactory;
    protected $table = "entidades_simuladas";

    public function entidad()
    {
        return $this->belongsTo(Entidad::class,'entidad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id', 'id');
    }

    public function olduser()
    {
        return $this->belongsTo(User::class,'old_editor', 'id');
    }

    public function creador()
    {
        return $this->belongsTo(Entidad::class,'creator_id');
    }

    public function analisisproyectos(){
        return $this->belongsTo(AnalisisProyectos::class,'id_analisis', 'id');
    }

    public function lastConcesion(){
        return $this->hasMany(Concessions::class,'custom_field_cif','cif_original')->orderByDesc('fecha');
    }

    public function analisis360(){
        return $this->belongsTo(Analisis360::class, 'id', 'simulacion_id');
    }

    public function einforma()
    {
        return $this->belongsTo(Einforma::class,'cif_original','identificativo')->where('esMercantil', 0)->latest('updated_at');
    }

    public function einformaMercantil()
    {
        return $this->belongsTo(Einforma::class,'cif_original','identificativo')->where('esMercantil', 1)->latest();
    }

    public function temporalLink(){
        return $this->belongsTo(temporalLink::class,'id','perfil_financiero_simulado_id');
    }

}
