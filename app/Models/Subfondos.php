<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subfondos extends Model
{
    use HasFactory;

    protected $table = "subfondos";

    function nivelsuperior(){        
        return $this->belongsTo(\App\Models\FondosSubfondos::class, 'id', 'subfondo_id')->where('nivel', '>', 1);        
    }
}
