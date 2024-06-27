<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ayudas extends Model
{
    use HasFactory;

    protected $appends = ["intereses"];

    protected $casts = [
        'intereses' => 'array'
      ];

    protected $table = 'convocatorias_ayudas';

    public function getInteresesAttribute() {

        $intereses = [];

        if($this->PerfilFinanciacion !== null && $this->PerfilFinanciacion != "null"){
            foreach(json_decode($this->PerfilFinanciacion,true) as $interes){
                $inte = DB::table('Intereses')->where('id_zoho', $interes)->first();
                $intereses[] = $inte->Nombre;
            }
        }

        if($this->perfiles !== null && $this->perfiles != "null"){
            $intereses = [];
            foreach(json_decode($this->perfiles,true) as $interes){
                $inte = DB::table('Intereses')->where('id_zoho', $interes)->first();
                $intereses[] = $inte->Nombre;
            }
        }   
        
        return array_unique($intereses);
    }

    function encajes(){
        return $this->hasMany(Encaje::class, 'Ayuda_id', 'id');
    }

    function organo(){
        return $this->belongsTo(Organos::class, 'Organismo', 'id');
    }

    function departamento(){
        return $this->belongsTo(Departamentos::class, 'Organismo', 'id');
    }

    function convocatoria(){
        return $this->belongsTo(Convocatorias::class, 'id_ayuda', 'id');
    }

    function relacionadas(){
        return $this->hasMany(AyudasRelacionadas::class,'ayuda_id','id')->orderByDesc('score');
    }

    function proyectos(){
        return $this->hasMany(Proyectos::class,'idAyudaAcronimo','IdConvocatoriaStr');
    }

    function rawdataEU(){
        return $this->belongsTo(ConvocatoriasEURawData::class, 'id_raw_data', 'id');
    }

    function chatgptdata(){
        return $this->hasMany(ChatGPTData::class,'convocatoria_id','id');
    }

    function translations($lang){
        return $this->hasMany(TextTranslations::class,'convocatoria_id','id')->where('idioma_traducido', $lang);
    }

    function keywords(){
        return $this->hasMany(ChatGPTAyudasKeywords::class, 'id', 'id_ayuda');
    }
}
