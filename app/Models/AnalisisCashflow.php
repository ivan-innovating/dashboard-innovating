<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisCashflow extends Model
{
    use HasFactory;

    protected $table = "analisis_cashflow";

    function cashflow(){
        return $this->belongsTo(AyudasCashFlow::class, 'id_cashflow', 'id');
    }

    function ayuda(){
        return $this->belongsTo(Convocatorias::class, 'id_ayuda', 'id');
    }

    function convocatoria(){
        return $this->belongsTo(Ayudas::class, 'id_convocatoria', 'id');
    }

    function editor(){
        return $this->belongsTo(User::class, 'id_editor', 'id');
    }

    function entidad(){
        return $this->belongsTo(User::class, 'id_entidad', 'id');
    }

    function financiacion(){
        return $this->belongsTo(AyudasFinanciacionCashFlow::class, 'tipo_financiacion', 'id');
    }
}
