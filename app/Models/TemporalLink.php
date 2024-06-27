<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporalLink extends Model
{
    use HasFactory;

    protected $table = "temporal_links";

    public function perfil_simulado(){
        return $this->belongsTo(EntidadesSimuladas::class,'perfil_financiero_simulado_id','id');
    }

    public function perfil(){
        return $this->belongsTo(PerfilesFinancieros::class,'perfil_financiero_id','id');
    }

    public function creator(){
        return $this->belongsTo(User::class,'creator_id','id');
    }
}
