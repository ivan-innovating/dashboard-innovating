<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Lionix\SeoManager\Traits\Appends;
use App\Events\EntitySaving;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Entidad extends Model
{
    use HasFactory;

    protected $table = 'entidades';

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'users_entidades', 'entidad_id', 'users_id')->withPivot(['role']);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($entidad) {
            if(Auth::check()){
                $entidad->UpdatedBy = Auth::user()->email;
            }else{
                $entidad->UpdatedBy = "system_user";
            }
        });
    }

    public function einforma()
    {
        return $this->belongsTo(Einforma::class,'CIF','identificativo')->latest('anioBalance');
    }

    public function simulaciones()
    {
        return $this->hasMany(EntidadesSimuladas::class,'creator_id','id')->where('created_at', '>=', Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'));
    }

    public function organo()
    {
        return $this->belongsTo(Organos::class,'idOrganismo','id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class,'idOrganismo','id');
    }

    public function invetigadores(){
        return $this->hasMany(Investigadores::class,'id_ultima_experiencia','id');
    }

    public function clientes(){
        return $this->hasMany(ConsultorasClientes::class,'cliente_id','id')->where('activo', 1);
    }

    public function zohoMails(){
        return $this->hasMany(ZohoMails::class,'CIF','CIF');
    }

    public function sellopyme()
    {
        return $this->hasMany(Pymes::class,'CIF','CIF')->orderByDesc('validez');
    }

    public function patentes(){
        return $this->hasMany(Patentes::class,'CIF','CIF');
    }

    public function concesions(){
        return $this->hasMany(Concessions::class,'custom_field_cif','CIF');
    }

    public function lastConcesion(){
        return $this->hasMany(Concessions::class,'custom_field_cif','CIF')->orderByDesc('fecha');
    }

    public function einformas()
    {
        return $this->hasMany(Einforma::class,'CIF','identificativo');
    }

    public function textosElastic(){
        return $this->belongsTo(TextosElastic::class,'CIF','CIF');
    }

    public function perfilFinanciero(){
        return $this->hasOne(PerfilesFinancieros::class,'id_entidad','id')->orderByDesc('activo')->where('activo', 1);
    }

    public function proyectosLider(){
        return $this->hasMany(proyectos::class,'empresaPrincipal','CIF');
    }

    public function proyectosParticipante(){
        return $this->hasMany(Participantes::class,'cif_participante','CIF');
    }

    public function elasticData(){
        return $this->belongsTo(ElasticDataTable::class,'CIF','elastic_id');
    }
    
    function keywords(){
        return $this->belongsTo(ChatGPTCompanyKeywords::class, 'id', 'id_entidad');
    }
}

