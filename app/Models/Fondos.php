<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fondos extends Model
{
    use HasFactory;

    function subfondos(){
        return $this->hasMany(FondosSubfondos::class, 'fondo_id', 'id');
    }
}
