<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AyudasCashFlow extends Model
{
    use HasFactory;

    protected $table = "ayudas_cashflow";

    function editor(){
        return $this->belongsTo(\App\Models\User::class, 'id_editor', 'id');
    }

    function ayuda(){
        return $this->belongsTo(\App\Models\Convocatorias::class, 'id_ayuda', 'id');
    }

    function financiaciones(){
        return $this->hasMany(\App\Models\AyudasFinanciacionCashFlow::class, 'id_cashflow', 'id');
    }

    function tipoproyecto(){
        return $this->hasMany(\App\Models\AyudasTipoProyectoCashFlow::class, 'id_cashflow', 'id');
    }
}
