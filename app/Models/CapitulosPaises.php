<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitulosPaises extends Model
{
    use HasFactory;

    protected $table = "capitulos_paises";

    function fondoGeneral(){        
        return $this->belongsTo(\App\Models\Capitulos::class, 'id_fondo_general', 'id');
    }
}
