<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FondosSubfondos extends Model
{
    use HasFactory;

    protected $table = "fondos_subfondos";

    function fondo(){        
        return $this->belongsTo(\App\Models\Subfondos::class, 'subfondo_id', 'id');        
    }

    function padre(){
        return $this->belongsTo(\App\Models\Subfondos::class, 'padre_subfondo_id', 'id');                
    }
}
