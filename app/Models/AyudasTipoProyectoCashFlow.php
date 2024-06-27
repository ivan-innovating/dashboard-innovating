<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AyudasTipoProyectoCashFlow extends Model
{
    use HasFactory;

    protected $table = "ayudas_tipos_proyectos_cashflow";

    function financiacion(){
        return $this->belongsTo(\App\Models\AyudasFinanciacionCashFlow::class, 'id_tipo_financiacion', 'id');
    }

}
